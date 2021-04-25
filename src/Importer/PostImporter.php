<?php
/**
 * The default import handler for post objects.
 */

namespace Geniem\Oopi\Importer;

use Geniem\Oopi\Attribute\Meta;
use Geniem\Oopi\Exception\AttributeSaveException;
use Geniem\Oopi\Exception\LanguageException;
use Geniem\Oopi\Exception\PostException as PostException;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Localization\Polylang as Polylang;
use Geniem\Oopi\Log;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Settings;
use Geniem\Oopi\Storage;
use Geniem\Oopi\Util;

/**
 * Class PostImportHandler
 *
 * @package Geniem\Oopi\Handler
 */
class PostImporter implements Importer {

    /**
     * Error scope.
     */
    const ESCOPE = 'post';

    /**
     * The error handler.
     *
     * @var ErrorHandler
     */
    protected ErrorHandler $error_handler;

    /**
     * An array holding save functions already run.
     *
     * @var array
     */
    protected array $save_state = [];

    /**
     * This value is true when rolling back a previous import state.
     * The rollback mode skips validations and logging.
     *
     * @var bool
     */
    protected bool $rollback_mode = false;

    /**
     * Set this to true to skip validation and force saving.
     *
     * @var bool
     */
    protected bool $force_save = false;

    /**
     * Holds the importable.
     *
     * @var PostImportable
     */
    protected PostImportable $importable;

    /**
     * Get all save functions that have been run.
     *
     * @return array
     */
    public function get_savestate() {
        return $this->save_state;
    }

    /**
     * Use this to save the state of run save functions.
     *
     * @param string $save_state The object key for the saved data.
     */
    public function set_save_state( string $save_state ) {
        $this->save_state[ $save_state ] = $save_state;
    }

    /**
     * Check if a specific object has been saved.
     *
     * @param string $saved The object key.
     * @return boolean
     */
    public function is_saved( string $saved ) {
        return isset( $this->save_state[ $saved ] );
    }

    /**
     * Get the force_save.
     *
     * @return bool
     */
    public function is_force_save() : bool {
        return $this->force_save;
    }

    /**
     * Set the force_save.
     *
     * @param bool $force_save The force_save.
     *
     * @return self Return self to enable chaining.
     */
    public function set_force_save( bool $force_save ) : self {
        $this->force_save = $force_save;

        return $this;
    }

    /**
     * Import the post into WordPress.
     *
     * @param Importable        $importable    The object to be imported.
     * @param ErrorHandler|null $error_handler An optional error handler.
     *
     * @return int|null On success, the WP item id is returned, null on failure.
     * @throws TypeException Thrown if the importable is not a post importable.
     * @throws PostException Thrown if the post data is not valid.
     */
    public function import( Importable $importable, ?ErrorHandler $error_handler = null ) : ?int {
        if ( ! $importable instanceof PostImportable ) {
            throw new TypeException( 'The importable passed for post importer must of type: ' . PostImportable::class );
        }

        // "Typecast" the importable.
        $this->importable = $importable;

        $this->error_handler = $error_handler ?? new OopiErrorHandler( self::ESCOPE );

        // If this is not forced or a rollback save, check for errors before the saving process.
        if ( ! $this->is_force_save() || ! $this->rollback_mode ) {
            $valid = $this->importable->validate();
            if ( ! $valid ) {
                // Log this import.
                new Log( $this );

                throw new PostException(
                    __( 'The post data was not valid. The import was canceled.', 'oopi' ),
                    0,
                    $this->error_handler->get_errors()
                );
            }
        }

        // Hook for running functionalities before saving the post.
        do_action( 'oopi_before_post_save', $this );

        $post_arr = (array) $this->importable->get_post();

        // Add filters for data modifications before and after importer related database actions.
        add_filter( 'wp_insert_post_data', [ $this, 'pre_insert_post' ], 1 );
        add_filter( 'wp_insert_post', [ $this, 'after_insert_post' ], 1 );

        // Run the WP save function.
        $post_id = wp_insert_post( $post_arr );

        $insert_err = '';
        if ( empty( $post_id ) ) {
            $insert_err = __(
                'An unknown error occurred while inserting the post. The returned post id was empty.', 'oopi'
            );

        }
        if ( $post_id instanceof \WP_Error ) {
            $insert_err = $post_id->get_error_message();
        }
        if ( $insert_err ) {
            $this->error_handler->set_error( 'post', $post_arr, $insert_err );

            // Log this import.
            new Log( $this );

            throw new PostException(
                __( 'Unable to insert the post. The import was canceled.', 'oopi' ),
                0,
                $this->error_handler->get_errors()
            );
        }

        // Identify the post, if not yet done.
        if ( empty( $this->importable->get_post_id() ) ) {
            $this->importable->set_post_id( $post_id );
            $this->identify();
        }

        // Save localization data.
        if ( ! empty( $this->importable->get_language() ) ) {
            $this->save_language();
        }

        // Save attachments.
        if ( ! empty( $this->importable->get_attachments() ) ) {
            $this->save_attachments();
        }

        // Save metadata.
        if ( ! empty( $this->importable->get_meta() ) ) {
            $this->save_meta();
        }

        // Save taxonomies.
        if ( ! empty( $this->importable->get_terms() ) ) {
            $this->save_terms();
        }

        // Save acf data.
        if ( ! empty( $this->importable->get_acf() ) ) {
            $this->save_acf();
        }

        // If this is not forced or a rollback save, check for errors after save process.
        if ( ! $this->is_force_save() || ! $this->rollback_mode ) {
            $valid = $this->importable->validate();
            if ( ! $valid ) {
                // Log this import.
                new Log( $this );

                $rolled_back = $this->rollback();

                // Set the correct error message.
                $err = $rolled_back ?
                    // Rollback error message
                    __(
                        'An error occurred while saving the import data. Rolled back the last successful import.',
                        'oopi'
                    ) :
                    // Default error message
                    __( 'An error occurred while saving the import data. Set the post status to "draft".', 'oopi' );

                throw new PostException(
                    $err,
                    0,
                    $this->error_handler->get_errors()
                );
            }
        }

        // This logs a successful import.
        new Log( $this );

        // Remove the custom filters.
        remove_filter( 'wp_insert_post_data', [ $this, 'pre_insert_post' ], 1 );
        remove_filter( 'wp_insert_post', [ $this, 'after_insert_post' ], 1 );

        // Hook for running functionalities after saving the post.
        do_action( 'oopi_after_post_save', $this );

        $this->importable->is_imported();

        return $post_id;
    }

    /**
     * Saves the attachments of the post.
     * Currently supports images.
     *
     * TODO: Refactor and move logic to the attachment importable and the attachment importer.
     *
     * @todo add support for other media formats too
     */
    protected function save_attachments() {
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

        $attachment_prefix   = Settings::get( 'attachment_prefix' );
        $attachment_language = $this->importable->get_language();

        foreach ( $this->importable->get_attachments() as &$attachment ) {

            $attachment_id      = Util::get_prop( $attachment, 'id' );
            $attachment_src     = Util::get_prop( $attachment, 'src' );
            $attachment_post_id = Storage::get_attachment_post_id_by_attachment_id( $attachment_id );
            $attachment_oopi_id = $attachment_prefix . $attachment_id;

            if ( empty( $attachment_src ) || empty( $attachment_id ) ) {
                $this->error_handler->set_error(
                    'attachment', __( 'The attachment object has missing parameters.', 'oopi' ), $attachment
                );
                continue;
            }

            // Check if attachment doesn't exists, and upload it.
            if ( ! $attachment_post_id ) {

                // Insert upload attachment from url
                $attachment_post_id = $this->insert_attachment_from_url(
                    $attachment_src,
                    $attachment,
                    $this->importable->get_post_id()
                );

                // Something went wrong.
                if ( is_wp_error( $attachment_post_id ) ) {
                    // @codingStandardsIgnoreStart
                    $this->error_handler->set_error( 'attachment', $attachment, __( 'An error occurred uploading the file.', 'oopi' ) );
                    // @codingStandardsIgnoreEnd
                }

                if ( $attachment_post_id ) {
                    // Set indexed meta for fast queries.
                    // Depending on the attachment prefix this would look something like:
                    // meta_key             | meta_value
                    // oopi_attachment_{1234} | 1234
                    update_post_meta( $attachment_post_id, $attachment_oopi_id, $attachment_id );
                    // Set the generally queryable id.
                    // Depending on the attachment prefix this would look something like:
                    // meta_key       | meta_value
                    // oopi_attachment  | 1234
                    update_post_meta( $attachment_post_id, rtrim( $attachment_prefix, '_' ), $attachment_id );

                    // Set the attachment locale if Polylang is active.
                    if ( Polylang::pll() ) {
                        $attachment_language = Util::get_prop( $this->importable->get_i18n(), 'locale' );

                        if ( $attachment_language ) {
                            Polylang::set_attachment_language( $attachment_post_id, $attachment_language );
                        }
                    }
                }
            }

            // Update attachment meta and handle translations
            if ( $attachment_post_id ) {

                // Get attachment translations.
                if ( Polylang::pll() ) {
                    $attachment_post_id = Polylang::get_attachment_by_language(
                        $attachment_post_id,
                        $attachment_language
                    );
                }

                // Update attachment info.
                $attachment_args = [
                    'ID'           => $attachment_post_id,
                    'post_title'   => Util::get_prop( $attachment, 'title' ),
                    'post_content' => Util::get_prop( $attachment, 'description' ),
                    'post_excerpt' => Util::get_prop( $attachment, 'caption' ),
                ];

                // Save the attachment post object data
                wp_update_post( $attachment_args );

                // Get the alt text if set.
                $alt_text = Util::get_prop( $attachment, 'alt' );

                // If alt was empty, use caption as an alternative text.
                $alt_text = $alt_text ?: Util::get_prop( $attachment, 'caption' );

                if ( $alt_text ) {
                    // Save image alt text into attachment post meta
                    update_post_meta( $attachment_post_id, '_wp_attachment_image_alt', $alt_text );
                }

                // Set the attachment post_id.
                $wp_id = Util::set_prop( $attachment, 'post_id', $attachment_post_id );
                // Store the ids to the importable.
                $this->importable->map_attachment_id( $attachment_oopi_id, $wp_id );
            }
        }

        // Done saving.
        $this->set_save_state( 'attachments' );
    }

    /**
     * Insert an attachment from an URL address.
     *
     * @param string $attachment_src Source file url.
     * @param object $attachment     Post class instances attachment.
     * @param int    $post_id        Attachments may be associated with a parent post or page.
     *                               Specify the parent's post ID, or 0 if unattached.
     *
     * @return int   $attachment_id
     */
    protected function insert_attachment_from_url( $attachment_src, $attachment, $post_id ) {
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

        // Get filename from the url.
        $file_name = basename( $attachment_src );
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

        // Construct file local url.
        $tmp_folder  = Settings::get( 'tmp_folder' );
        $local_image = $tmp_folder . $file_name;

        // Copy file to local image location
        copy( $attachment_src, $local_image, $stream_context );

        // If exif_read_data is callable and file type could contain exif data.
        if (
            is_callable( 'exif_read_data' ) &&
            in_array( $exif_imagetype, $exif_supported_imagetypes, true )
        ) {
            // Manipulate image exif data to prevent.
            $this->strip_unsupported_exif_data( $local_image );
        }

        // Get file from local temp folder.
        $file_content = file_get_contents( $local_image, false, $stream_context ); // phpcs:ignore

        // Upload file to uploads.
        $upload = wp_upload_bits( $file_name, null, $file_content );

        // After upload process we are free to delete the tmp image.
        unlink( $local_image );

        // If error occured during upload return false.
        if ( ! empty( $upload['error'] ) ) {
            return false;
        }

        // File variables
        $file_path     = $upload['file'];
        $file_type     = wp_check_filetype( $file_name, null );
        $wp_upload_dir = wp_upload_dir();

        // wp_insert_attachment post info
        $post_info = [
            'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
            'post_mime_type' => $file_type['type'],
            'post_title'     => Util::get_prop( $attachment, 'title' ),
            'post_content'   => Util::get_prop( $attachment, 'description' ),
            'post_excerpt'   => Util::get_prop( $attachment, 'caption' ),
            'post_status'    => 'inherit',
        ];

        // Insert attachment to the database.
        $attachment_id = wp_insert_attachment( $post_info, $file_path, $post_id, true );

        // Generate post thumbnail attachment meta data.
        $attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );

        // Assign metadata to an attachment.
        wp_update_attachment_metadata( $attachment_id, $attachment_data );

        return $attachment_id;
    }

    /**
     * If exif_read_data() fails, remove exif data from the image file
     * to prevent errors in WordPress core.
     *
     * @param string $local_image       Local url for an image.
     * @return void No return.
     */
    protected function strip_unsupported_exif_data( $local_image ) {

        // Variable for exif data errors in PHP
        $php_exif_data_error_exists = false;

        // Check for PHP exif_read_data function errors!
        try {
            exif_read_data( $local_image );
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
                $this->error_handler->set_error(
                    'Unable to write image. Error: ' . $e->getMessage(),
                    $local_image
                );
            }
        }
    }

    /**
     * Saves the metadata of the post.
     *
     * @return void
     */
    protected function save_meta() {
        $meta = $this->importable->get_meta();
        if ( is_array( $meta ) ) {
            array_walk( $meta, function( Meta $attr ) {
                try {
                    $attr->save();
                }
                catch ( \Exception $e ) {
                    $this->error_handler->set_error(
                        'Unable to save meta attribute. Error: ' . $e->getMessage(),
                        $attr
                    );
                }
            } );
        }

        // Saving meta is done.
        $this->set_save_state( 'meta' );
    }

    /**
     * Sets the terms of a post and create taxonomy terms
     * if they do not exist yet.
     */
    protected function save_terms() {
        $terms = $this->importable->get_terms();
        if ( is_array( $terms ) ) {
            $term_ids_by_tax = [];
            foreach ( $terms as $term ) {
                // Import term if it's not already imported.
                if ( ! $term->is_imported() ) {
                    $term_id = $term->get_importer()->import( $term, $this->error_handler );
                }

                // Map the ids into the relationship array.
                $term_ids_by_tax[ $term->get_taxonomy() ]   = $term_ids_by_tax[ $term->get_taxonomy() ] ?? [];
                $term_ids_by_tax[ $term->get_taxonomy() ][] = $term_id;
            }
            foreach ( $term_ids_by_tax as $taxonomy => $terms ) {
                // Set terms for the post object.
                wp_set_object_terms( $this->importable->get_post_id(), $terms, $taxonomy );
            }
        }

        // Done saving.
        $this->set_save_state( 'taxonomies' );
    }

    /**
     * Saves the acf data of the post.
     */
    protected function save_acf() {
        foreach ( $this->importable->get_acf() as $field_attribute ) {
            try {
                $field_attribute->save();
            }
            catch ( AttributeSaveException $exception ) {
                $this->error_handler->set_error(
                    $exception->getMessage(),
                    $exception
                );
            }
        }

        // Done saving.
        $this->set_save_state( 'acf' );
    }

    /**
     * Save language data.
     */
    protected function save_language() {
        try {
            $this->importable->get_language()->save();
        }
        catch ( LanguageException $e ) {
            $this->error_handler->set_error( $e->getMessage(), $e );
        }
    }

    /**
     * Adds postmeta rows for matching a WP post with the OOPI id.
     */
    public function identify() {
        $oopi_id = $this->importable->get_oopi_id();

        // Set the queryable identificator.
        // Example: meta_key = 'oopi_id', meta_value = 12345
        update_post_meta( $this->importable->get_post_id(), Storage::get_idenfiticator(), $oopi_id );

        $index_key = Storage::format_query_key( $oopi_id );

        // Set the indexed indentificator.
        // Example: meta_key = 'oopi_id_12345', meta_value = 12345
        update_post_meta( $this->importable->get_post_id(), $index_key, $oopi_id );
    }

    /**
     * This function creates a filter for the 'wp_insert_post_data' hook
     * which is enabled only while importing post data with Oopi.
     * Use this to customize the imported data before any database actions.
     *
     * @param object $post_data The post data to be saved.
     *
     * @return mixed
     */
    public function pre_insert_post( $post_data ) {
        // If this instance has time values, set them here and override WP automation.
        if (
            isset( $this->importable->get_post()->post_date ) &&
            $this->importable->get_post()->post_date !== '0000-00-00 00:00:00'
        ) {
            $post_data['post_date']     = $this->importable->get_post()->post_date;
            $post_data['post_date_gmt'] = \get_gmt_from_date( $this->importable->get_post()->post_date );
        }
        if (
            isset( $this->importable->get_post()->post_modified ) &&
            $this->importable->get_post()->post_modified !== '0000-00-00 00:00:00'
        ) {
            $post_data['post_modified']     = $this->importable->get_post()->post_modified;
            $post_data['post_modified_gmt'] = \get_gmt_from_date( $this->importable->get_post()->post_modified );
        }

        return apply_filters( 'oopi_pre_insert_post', $post_data, $this->importable->get_oopi_id() );
    }

    /**
     * This function creates a filter for the 'wp_insert_post_data' action hook.
     * Use this to customize the imported data after 'wp_insert_post()' is run.
     *
     * @param array $postarr Post data array.
     * @return array
     */
    public function after_insert_post( $postarr ) {
        return apply_filters( 'oopi_after_insert_post', $postarr, $this->importable->get_oopi_id() );
    }

    /**
     * Restores a post's state back to the last successful import.
     *
     * @return boolean Did we roll back or not?
     * @throws PostException If the rollback fails, an exception is thrown.
     */
    protected function rollback() {
        // Set the rollback mode.
        $this->rollback_mode = true;

        $last_import = Log::get_last_successful_import( $this->importable->get_post_id() );

        if ( $last_import && Settings::get( 'rollback_disable' ) !== true ) {
            // First delete all imported data.
            $this->delete_data();

            // Save the previous import again.
            $data = $last_import->get_data();
            $this->importable->set_data( $data );
            $this->import();

            $this->rollback_mode = false;

            return true;
        }
        else {
            // Set post status to 'draft' to hide posts containing errors.
            $update_status = [
                'ID'          => $this->importable->get_post_id(),
                'post_status' => 'draft',
            ];

            // Update the status into the database
            wp_update_post( $update_status );

            return false;
        }
    }

    /**
     * Delete all data related to a single post.
     * Note: This keeps the basic post data intact int the posts table.
     */
    public function delete_data() {

        // This removes most of data related to a post.
        Storage::delete_post_meta_data( $this->importable->get_post_id() );

        // Delete all term relationships.
        \wp_delete_object_term_relationships( $this->importable->get_post_id(), \get_taxonomies() );

        // Run custom action for custom data.
        // Use this if the data is not in the postmeta table.
        do_action( 'oopi_delete_data', $this->importable->get_post_id() );
    }
}
