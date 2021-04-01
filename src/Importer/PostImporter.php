<?php
/**
 * The default import handler for post objects.
 */

namespace Geniem\Oopi\Importer;

use Geniem\Oopi\Attribute\PostMeta;
use Geniem\Oopi\Exception\PostException as PostException;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\Post;
use Geniem\Oopi\Importable\Term;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Localization\Controller;
use Geniem\Oopi\Localization\Polylang as Polylang;
use Geniem\Oopi\Log;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Settings;
use Geniem\Oopi\Storage;
use Geniem\Oopi\Util;
use mysql_xdevapi\Exception;

/**
 * Class PostImportHandler
 *
 * @package Geniem\Oopi\Handler
 */
class PostImporter extends BaseImporter {

    /**
     * The importable post.
     *
     * @var Post
     */
    protected $importable;

    /**
     * The error handler.
     *
     * @var ErrorHandler
     */
    protected $error_handler;

    /**
     * An array holding save functions already run.
     *
     * @var array
     */
    protected $save_state = [];

    /**
     * This value is true when rolling back a previous import state.
     * The rollback mode skips validations and logging.
     *
     * @var bool
     */
    protected $rollback_mode = false;

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
     * ImportableAccessing constructor.
     *
     * @param Importable        $importable    The importable object.
     * @param ErrorHandler|null $error_handler If no error handler is set, use the default one.
     *
     * @throws TypeException Exception is thrown if the passed importable is not a post importable.
     */
    public function __construct( Importable $importable, ?ErrorHandler $error_handler = null ) {
        if ( ! $importable instanceof Post ) {
            throw new TypeException( 'The importable passed for post importer must of type: ' . Post::class );
        }

        if ( empty( $error_handler ) ) {
            $this->error_handler = new OopiErrorHandler();
        }

        parent::__construct( $importable );
    }

    /**
     * Import the post into WordPress.
     *
     * @return int|null The WP post id on success, null on failure.
     * @throws PostException Exception is thrown if the import process fails.
     */
    public function import() : ?int {
        // If this is not forced or a rollback save, check for errors before the saving process.
        if ( ! $this->importable->is_force_save() || ! $this->rollback_mode ) {
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
        if ( ! empty( $this->language ) ) {
            Controller::save_language( $this->importable );
        }

        // Save attachments.
        if ( ! empty( $this->attachments ) ) {
            $this->save_attachments();
        }

        // Save metadata.
        if ( ! empty( $this->meta ) ) {
            $this->save_meta();
        }

        // Save taxonomies.
        if ( ! empty( $this->taxonomies ) ) {
            $this->save_taxonomies();
        }

        // Save acf data.
        if ( ! empty( $this->importable->get_acf() ) ) {
            $this->save_acf();
        }

        // If this is not forced or a rollback save, check for errors after save process.
        if ( ! $this->importable->is_force_save() || ! $this->rollback_mode ) {
            $valid = $this->validate();
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

        return $post_id;
    }

    /**
     * Saves the attachments of the post.
     * Currently supports images.
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
        $attachment_language = Util::get_prop( $this->importable->get_i18n(), 'locale' );

        foreach ( $this->importable->get_attachments() as &$attachment ) {

            $attachment_id      = Util::get_prop( $attachment, 'id' );
            $attachment_src     = Util::get_prop( $attachment, 'src' );
            $attachment_post_id = Storage::get_attachment_post_id_by_attachment_id( $attachment_id );

            if ( empty( $attachment_src ) || empty( $attachment_id ) ) {
                // @codingStandardsIgnoreStart
                $this->error_handler->set_error( 'attachment', $attachment, __( 'The attachment object has missing parameters.', 'oopi' ) );
                // @codingStandardsIgnoreEnd
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
                    update_post_meta( $attachment_post_id, $attachment_prefix . $attachment_id, $attachment_id );
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
                $this->importable->get_attachment_ids()[ $attachment_prefix . $attachment_id ] = Util::set_prop( $attachment, 'post_id', $attachment_post_id );
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
                // Unable to edit the image exif data.
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
            foreach ( $meta as $key => $value ) {
                // Cast the raw data into an attribute object if it is not one already.
                $post_meta = $value instanceof Attribute ?
                    $value :
                    // If attribute is in raw format, create a default post meta attribute with the default saver.
                    new PostMeta( $this->importable, $key, $value, null, $this->error_handler );

                $post_meta->save();
            }
        }

        // Saving meta is done.
        $this->set_save_state( 'meta' );
    }

    /**
     * Sets the terms of a post and create taxonomy terms
     * if they do not exist yet.
     */
    protected function save_taxonomies() {
        $taxonomies = $this->importable->get_taxonomies();
        if ( is_array( $taxonomies ) ) {
            $term_ids_by_tax = [];
            foreach ( $taxonomies as $term ) {
                $taxonomy = $term->get_taxonomy();
                $wp_term  = $term->get_term();

                // If the term does not exist, create it.
                if ( ! $wp_term ) {
                    $result = Storage::create_new_term( $term, $this->importable );
                    if ( is_wp_error( $result ) ) {
                        // Skip erroneous terms.
                        continue;
                    }
                }
                $wp_term = $term->get_term();

                // Ensure identification. Data is only set once.
                $term->identify();

                // Handle localization.
                if ( $term->get_language() !== null ) {
                    Controller::set_term_language( $term, $this->importable );
                }

                // Add term id.
                if ( isset( $term_ids_by_tax[ $taxonomy ] ) ) {
                    $term_ids_by_tax[ $taxonomy ][] = $wp_term->term_id;
                }
                else {
                    $term_ids_by_tax[ $taxonomy ] = [ $wp_term->term_id ];
                }
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

        // Bail if ACF is not activated.
        if ( ! function_exists( 'get_field' ) ) {
            $this->error_handler->set_error(
                'acf',
                $this->importable->get_acf(),
                __(
                    'Advanced Custom Fields is not active! Please install and activate the plugin to save ACF data.',
                    'oopi'
                )
            );
            return;
        }

        if ( ! is_array( $this->importable->get_acf() ) ) {
            $this->error_handler->set_error(
                'acf',
                $this->importable->get_acf(),
                __( 'Advanced Custom Fields data is set but it is not an array.', 'oopi' )
            );
            return;
        }

        foreach ( $this->importable->get_acf() as $acf_row ) {
            // The key must be set.
            if ( empty( Util::get_prop( $acf_row, 'key', '' ) ) ) {
                continue;
            }

            $type  = Util::get_prop( $acf_row, 'type', 'default' );
            $key   = Util::get_prop( $acf_row, 'key', '' );
            $value = Util::get_prop( $acf_row, 'value', '' );

            switch ( $type ) {
                case 'taxonomy':
                    $terms = [];
                    foreach ( $value as $term ) {
                        if ( ! $term instanceof Term ) {
                            $term = ( new Term( Util::get_prop( 'oopi_id' ) ) )->set_data( $term );
                        }

                        // If the term does not exist, create it.
                        if ( ! $term->get_term() ) {
                            $result = Storage::create_new_term( $term, $this->importable );
                            if ( is_wp_error( $result ) ) {
                                // Skip erroneous terms.
                                continue;
                            }
                        }

                        // Ensure identification. Data is only set once.
                        $term->identify();

                        // Handle localization.
                        if ( $term->get_language() !== null ) {
                            Controller::set_term_language( $term, $this->importable );
                        }

                        $terms[] = $term->get_term_id();
                    }
                    if ( count( $terms ) ) {
                        update_field( $key, $terms, $this->importable->get_post_id() );
                    }
                    break;

                case 'image':
                    // Check if image exists.
                    $attachment_post_id = $this->importable->get_attachment_ids()[ $value ] ?? null;
                    if ( ! empty( $attachment_post_id ) ) {
                        update_field( $key, $attachment_post_id, $this->importable->get_post_id() );
                    }
                    else {
                        $err = __( 'Trying to set an image in an ACF field that does not exists.', 'oopi' );
                        $this->error_handler->set_error( 'acf', 'image_field', $err );
                    }
                    break;

                // @todo Test which field types require no extra logic.
                // Currently tested: 'select'
                default:
                    update_field( $key, $value, $this->importable->get_post_id() );
                    break;
            }
        }

        // Done saving.
        $this->set_save_state( 'acf' );
    }

    /**
     * Adds postmeta rows for matching a WP post with an external source.
     */
    public function identify() {
        $id_prefix = Settings::get( 'id_prefix' );

        // Remove the trailing '_'.
        $identificator = rtrim( $id_prefix, '_' );

        // Set the queryable identificator.
        // Example: meta_key = 'oopi_id', meta_value = 12345
        update_post_meta( $this->importable->get_post_id(), $identificator, $this->oopi_id );

        // Set the indexed indentificator.
        // Example: meta_key = 'oopi_id_12345', meta_value = 12345
        update_post_meta( $this->importable->get_post_id(), $id_prefix . $this->oopi_id, $this->oopi_id );
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
        if ( isset( $this->importable->get_post()->post_date ) &&
            $this->importable->get_post()->post_date !== '0000-00-00 00:00:00'
        ) {
            $post_data['post_date']     = $this->importable->get_post()->post_date;
            $post_data['post_date_gmt'] = \get_gmt_from_date( $this->importable->get_post()->post_date );
        }
        if ( isset( $this->importable->get_post()->post_modified ) &&
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
