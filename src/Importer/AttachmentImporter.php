<?php
/**
 * Controls importing attachments.
 */

namespace Geniem\Oopi\Importer;

use Geniem\Oopi\Exception\LanguageException;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\AttachmentImportable;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Settings;
use Geniem\Oopi\Storage;
use WP_Error;

/**
 * Class AttachmentImporter
 *
 * @package Geniem\Oopi\Importer
 */
class AttachmentImporter implements Importer {

    /**
     * Import the post into WordPress.
     *
     * @param Importable        $importable    The object to be imported.
     * @param ErrorHandler|null $error_handler An optional error handler.
     *
     * @return int|null On success, the WP item id is returned, null on failure.
     * @throws TypeException Thrown if the importable is not an attachment importable.
     */
    public function import( Importable $importable, ?ErrorHandler $error_handler = null ) : ?int {
        if ( ! $importable instanceof AttachmentImportable ) {
            throw new TypeException(
                __CLASS__ . ' can only import attachments of type: ' . AttachmentImportable::class
            );
        }

        $this->require_wp_core_files();

        $error_handler = $error_handler ?? $importable->get_error_handler();

        $oopi_id            = $importable->get_oopi_id();
        $attachment_src     = $importable->get_src();
        $attachment_post_id = $importable->get_wp_id() ??
            Storage::get_post_id_by_oopi_id(
                $oopi_id
            );

        $title       = $importable->get_title();
        $description = $importable->get_description();
        $caption     = $importable->get_caption();
        $alt         = $importable->get_alt();

        $attachment_language = $importable->get_language();

        if ( empty( $attachment_src ) ) {
            $error_handler->set_error( 'The attachment must have a "src" attribute.', $importable );
            return null;
        }

        // Filter the post id before trying to upload the file.
        $attachment_post_id = apply_filters( 'oopi_attachment_post_id', $attachment_post_id, $importable );

        // Check if the attachment doesn't exists, and upload it.
        if ( ! $attachment_post_id ) {

            // If this is a translatable attachment and the main attachment
            // is already uploaded, use the same file and insert the attachment.
            if (
                ! $attachment_post_id &&
                $attachment_language &&
                $attachment_language->get_main_oopi_id()
            ) {
                $attachment_post_id = $this->copy_attachment_from_main_language(
                    $importable,
                    $error_handler
                );

                // Something went wrong.
                if ( ! $attachment_post_id ) {
                    $error_handler->set_error(
                        'Copying the file from the main translation of the attachment failed.',
                        $importable
                    );
                    return null;
                }
            }
            else {
                // Insert upload attachment from url
                $upload = $this->insert_attachment_from_url(
                    $attachment_src,
                    $error_handler
                );

                // Fail fast if there isn't an url.
                if ( empty( $upload['url'] ) ) {
                    return null;
                }

                // Something went wrong.
                if ( ! empty( $upload['error'] ) ) {
                    $error_handler->set_error(
                        'An error occurred uploading the file : ' . $attachment_post_id, $upload
                    );
                    return null;
                }

                $attachment_post_id = $this->insert_attachment(
                    $importable,
                    $upload['file'],
                    $upload['url'],
                    $error_handler,
                    $importable->get_parent_wp_id()
                );

                if ( $attachment_post_id === null ) {
                    return null;
                }
            }
        }
        else {
            // Update attachment info.
            $attachment_args = [
                'ID'           => $attachment_post_id,
                'post_title'   => $title,
                'post_content' => $description,
                'post_excerpt' => $caption,
                'post_parent'  => $importable->get_parent_wp_id(),
            ];
            wp_update_post( $attachment_args );
        }

        // Ensure identification.
        $this->identify( $oopi_id, $attachment_post_id );

        // Handle translations.
        if ( $attachment_language ) {
            try {
                $attachment_language->save();
            }
            catch ( LanguageException $e ) {
                $error_handler->set_error( $e->getMessage(), $e );
            }
        }

        // If alt was empty, use caption as an alternative text.
        $alt_text = $alt ?: $caption;

        if ( $alt_text ) {
            // Save image alt text into attachment post meta
            update_post_meta( $attachment_post_id, '_wp_attachment_image_alt', $alt_text );
        }

        // Set the attachment post_id.
        $importable->set_wp_id( $attachment_post_id );

        // Save acf data.
        if ( ! empty( $importable->get_acf() ) ) {
            $this->save_acf( $importable );
        }

        // Store the ids to the importable.
        if ( $importable instanceof PostImportable ) {
            $importable->map_attachment_id( $oopi_id, $attachment_post_id );
        }

        return $attachment_post_id;
    }

    /**
     * Uses the file from the attachment of the main language.
     * This can be used to prevent multiple uploads for the same file
     * for translated attachments.
     *
     * @param AttachmentImportable $importable    The attachment to import.
     * @param ErrorHandler         $error_handler The error handler.
     *
     * @return int|null The newly inserted WP post for the attachment or null on error.
     */
    protected function copy_attachment_from_main_language(
        AttachmentImportable $importable,
        ErrorHandler $error_handler
    ) : ?int {
        if ( empty( $importable->get_language()->get_main_oopi_id() ) ) {
            return null;
        }

        $main_post_id = Storage::get_post_id_by_oopi_id( $importable->get_language()->get_main_oopi_id() );
        $main_post    = get_post( $main_post_id );
        $main_file    = get_attached_file( $main_post->ID );
        if ( $main_file ) {
            $attachment_post_id = $this->insert_attachment(
                $importable,
                $main_file,
                $main_post->guid,
                $error_handler,
                $importable->get_parent_wp_id()
            );

            return $attachment_post_id;
        }
    }

    /**
     * Insert an attachment from an URL address.
     *
     * @param string       $attachment_src Source file url.
     * @param ErrorHandler $error_handler  An error handler.
     *
     * @return array The response array from wp_upload_bits().
     */
    protected function insert_attachment_from_url(
        string $attachment_src,
        ErrorHandler $error_handler
    ) {

        // Fail fast if image doesn't exist.
        if ( $this->remote_file_exists( $attachment_src ) === false ) {
            return;
        }

        $stream_context = null;

        // If you want to ignore SSL Certificate chain errors, or just yeet it,
        // define OOPI_IGNORE_SSL in your migration worker and set it true.
        if ( defined( 'OOPI_IGNORE_SSL' ) && OOPI_IGNORE_SSL ) {

            $stream_context = stream_context_create( [
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ] );
        }

        // Get filename from the url and remove query params.
        $file_name = basename( parse_url( $attachment_src, PHP_URL_PATH ) );

        // Construct file local url.
        $tmp_folder  = Settings::get( 'tmp_folder' );
        $local_image = $tmp_folder . $file_name;

        // Copy file to local image location
        copy( $attachment_src, $local_image, $stream_context );

        // If SSL is ignored we need to use different methods to check file extension from the source file.
        // Always prefer to use SSL.
        if (
            defined( 'OOPI_IGNORE_SSL' ) &&
            OOPI_IGNORE_SSL &&
            is_callable( 'exif_read_data' )
        ) {

            $this->strip_exif_by_file_extension( $local_image, $attachment_src, $error_handler );
        }
        elseif ( is_callable( 'exif_read_data' ) ) {

            $this->strip_exif_by_exif_data( $local_image, $attachment_src, $error_handler );
        }

        // Get file from local temp folder.
        $file_content = file_get_contents( $local_image, false, $stream_context ); // phpcs:ignore

        // Upload file to uploads.
        $upload = \wp_upload_bits( $file_name, null, $file_content );

        // After upload process we are free to delete the tmp image.
        unlink( $local_image );

        return $upload;
    }

    /**
     * Remote file exists.
     *
     * @param string $remote_file_url
     * @return bool True if the file exists otherwise false.
     */
    private function remote_file_exists( string $remote_file_url ) : bool {

        $status_code = $this->get_http_status_code( $remote_file_url );

        // Only 200 will be ok here.
        if ( empty( $status_code ) || $status_code !== 200 ) {
            return false;
        }

        return true;
    }

    /**
     * Get HTTP status code.
     *
     * @param string $remote_file_url
     * @return null|int
     */
    private function get_http_status_code( string $remote_file_url ) {

        $file_headers = @get_headers( $remote_file_url, 1 );

        if ( $file_headers === false ) {
            return null;
        }

        // If there are redirects, the last status code is the effective one.
        $status_lines = is_array( $file_headers[0] ) ? end( $file_headers[0] ) : $file_headers[0];

        preg_match( '{HTTP/\d\.\d\s+(\d+)}', $status_lines, $match );

        return isset( $match[1]) ? (int)$match[1] : null;
    }

    /**
     * Strip exif by exif data.
     *
     * @param string       $local_image Local image path.
     * @param string       $attachment_src Source file url.
     * @param ErrorHandler $error_handler An error handler.
     *
     * @return array The response array from wp_upload_bits().
     */
    private function strip_exif_by_exif_data( $local_image, $attachment_src, $error_handler ) {

        // Get filename from the url and remove query params.
        $file_name = basename( parse_url( $attachment_src, PHP_URL_PATH ) );

        // Exif related variables
        $exif_imagetype            = exif_imagetype( $attachment_src );
        $exif_supported_imagetypes = [
            IMAGETYPE_JPEG,
            IMAGETYPE_TIFF_II,
            IMAGETYPE_TIFF_MM,
        ];

        // If the file name does not appear to contain a suffix, add it.
        if ( strpos( $file_name, '.' ) === false ) {
            $exif_types = [
                '.gif',
                '.jpeg',
                '.png',
                '.swf',
                '.psd',
                '.bmp',
                '.tiff',
                '.tiff',
                '.jpc',
                '.jp2',
                '.jpx',
                '.jb2',
                '.swc',
                '.iff',
                '.wbmp',
                '.xbm',
                '.ico',
                '.webp',
            ];
            // See: https://www.php.net/manual/en/function.exif-imagetype.php#refsect1-function.exif-imagetype-constants
            $file_name .= $exif_types[ $exif_imagetype - 1 ];
        }

        // If exif_read_data is callable and file type could contain exif data.
        if ( in_array( $exif_imagetype, $exif_supported_imagetypes, true ) ) {

            // Manipulate image exif data.
            $this->strip_unsupported_exif_data( $local_image, $error_handler );
        }
    }

    /**
     * Strip exif by file extension.
     *
     * @param string       $local_image Local image path.
     * @param string       $attachment_src Source file url.
     * @param ErrorHandler $error_handler An error handler.
     *
     * @return array The response array from wp_upload_bits().
     */
    private function strip_exif_by_file_extension( $local_image, $attachment_src, $error_handler ) {

        // Get filename from the url and remove query params.
        $file_name = basename( parse_url( $attachment_src, PHP_URL_PATH ) );

        // Exif related variables
        $file_extension = $this->get_file_extension_from_src( $attachment_src );

        // If not valid extension found skip.
        if ( empty( $file_extension ) ) {
            return;
        }

        $exif_supported_file_extensions = [
            'gif',
            'jpeg',
            'png',
            'swf',
            'psd',
            'bmp',
            'tiff',
            'tiff',
            'jpc',
            'jp2',
            'jpx',
            'jb2',
            'swc',
            'iff',
            'wbmp',
            'xbm',
            'ico',
            'webp',
        ];

        // If exif_read_data is callable and file type could contain exif data.
        if ( in_array( $file_extension, $exif_supported_file_extensions, true ) ) {

            // Manipulate image exif data.
            $this->strip_unsupported_exif_data( $local_image, $error_handler );
        }
    }

    /**
     * Get file extension from the url.
     *
     * @param string $attachment_src File URL.
     * @return string File extension.
     */
    private function get_file_extension_from_src( $attachment_src ) {

        $parsed_url = parse_url( $attachment_src );

        // Get the path component from the parsed URL.
        $path = $parsed_url['path'] ?? '';

        // Extract the file extension from the path.
        $file_extension = pathinfo( $path, PATHINFO_EXTENSION ) ?? '';
    }

    /**
     * If exif_read_data() fails, remove exif data from the image file
     * to prevent errors in WordPress core.
     *
     * @param string       $local_image   Local url for an image.
     * @param ErrorHandler $error_handler An error handler instanse.
     *
     * @return void No return.
     */
    protected function strip_unsupported_exif_data( string $local_image, ErrorHandler $error_handler ) {

        // Variable for exif data errors in PHP
        $php_exif_data_error_exists = false;

        // Check for PHP exif_read_data function errors!
        try {
            \exif_read_data( $local_image );
        }
        catch ( \Exception $e ) {
            $php_exif_data_error_exists = true;
        }

        // If image magic is installed and exif_data_error exists
        if ( class_exists( 'Imagick' ) && $php_exif_data_error_exists === true ) {

            // Run image through image magick
            try {
                $imagick_object = new \Imagick( realpath( $local_image ) );

                // Strip off all exif data to prevent PHP 5.6 and PHP 7.0 exif errors!
                $imagick_object->stripImage();

                // Write manipulated file to the tmp folder
                $imagick_file = $imagick_object->writeImage( $local_image );
            }
            catch ( \Exception $e ) {
                $error_handler->set_error(
                    'Unable to write image. Error: ' . $e->getMessage(),
                    $local_image
                );
            }
        }
    }

    /**
     * Creates the attachment and returns the created post id.
     *
     * @param AttachmentImportable $importable    The attachment importable.
     * @param string               $file_path     The file path.
     * @param string               $file_url      The WP file url to use as the GUID.
     * @param ErrorHandler         $error_handler The error handler.
     * @param int|null             $parent_wp_id  The parent post id to attach to.
     *
     * @return int|null
     */
    protected function insert_attachment(
        AttachmentImportable $importable,
        string $file_path,
        string $file_url,
        ErrorHandler $error_handler,
        ?int $parent_wp_id = 0
    ) : ?int {
        $file_type = wp_check_filetype( $file_path, null );

        $post_info = [
            'guid'           => $file_url,
            'post_mime_type' => $file_type['type'],
            'post_title'     => $importable->get_title(),
            'post_content'   => $importable->get_description(),
            'post_excerpt'   => $importable->get_caption(),
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
        ];

        // Insert attachment into WP.
        $attachment_id = wp_insert_attachment( $post_info, $file_path, $parent_wp_id, true );

        if ( $attachment_id instanceof WP_Error ) {
            $error_handler->set_error( $attachment_id->get_error_message(), $attachment_id );
            return null;
        }

        // Generate post thumbnail attachment meta data.
        $attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );

        // Assign metadata to an attachment.
        wp_update_attachment_metadata( $attachment_id, $attachment_data );

        return $attachment_id;
    }

    /**
     * Requires WP core files needed for image and file handling.
     */
    protected function require_wp_core_files() {
        // All of the following are required for the media_sideload_image function.
        if ( ! function_exists( '\media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        if ( ! function_exists( '\download_url' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( ! function_exists( '\wp_read_image_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
    }

    /**
     * Adds post meta rows for matching a WP attachment with an external file.
     *
     * @param string $oopi_id The OOPI id.
     * @param int    $wp_id   The WP id.
     */
    public function identify( string $oopi_id, int $wp_id ) {
        if ( Storage::get_post_id_by_oopi_id( $oopi_id ) ) {
            // Do not reset.
            return;
        }

        // Set the queryable identificator.
        // Example: meta_key = 'oopi_id', meta_value = 12345
        update_post_meta( $wp_id, Storage::get_idenfiticator(), $oopi_id );

        $index_key = Storage::format_query_key( $oopi_id );

        // Set the indexed indentificator.
        // Example: meta_key = 'oopi_id_12345', meta_value = 12345
        update_post_meta( $wp_id, $index_key, $oopi_id );
    }

    /**
     * Saves the acf data of the post.
     */
    protected function save_acf( $importable ) {

        foreach ( $importable->get_acf() as $field_attribute ) {
            try {
                $field_attribute->save();
            }
            catch ( \Exception $exception ) {
                $this->error_handler->set_error(
                    $exception->getMessage(),
                    $exception
                );
            }
        }
    }
}
