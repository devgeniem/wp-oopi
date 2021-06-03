<?php
/**
 * The default import handler for post objects.
 */

namespace Geniem\Oopi\Importer;

use Exception;
use Geniem\Oopi\Attribute\Meta;
use Geniem\Oopi\Attribute\PostThumbnail;
use Geniem\Oopi\Exception\AttributeException;
use Geniem\Oopi\Exception\LanguageException;
use Geniem\Oopi\Exception\PostException as PostException;
use Geniem\Oopi\Exception\RollbackException;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\AttachmentImportable;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Log;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Settings;
use Geniem\Oopi\Storage;

/**
 * Class PostImporter
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
     * @throws RollbackException Thrown if the rollback execution fails after failing the initial import.
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
        do_action( 'oopi_before_post_import', $this );

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
        if ( empty( $this->importable->get_wp_id() ) ) {
            $this->importable->set_wp_id( $post_id );
            $this->identify( $this->importable->get_oopi_id(), $post_id );
        }

        // Save localization data.
        if ( ! empty( $this->importable->get_language() ) ) {
            $this->save_language();
        }

        // Save attachments.
        if ( ! empty( $this->importable->get_attachments() ) ) {
            $this->import_attachments();
        }

        // Save the thumbnail.
        if ( ! empty( $this->importable->get_thumbnail() ) ) {
            $this->save_thumbnail();
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

        $this->importable->set_post( get_post( $post_id ) );

        $this->importable->set_imported( true );

        // Hook for running functionalities after saving the post.
        do_action( 'oopi_after_post_import', $this );

        return $post_id;
    }

    /**
     * Saves the attachments of the post.
     */
    protected function import_attachments() {
        $attachments     = $this->importable->get_attachments();
        $post_importable = $this->importable;

        array_walk( $attachments, function( AttachmentImportable $attachment ) use ( $post_importable ) {
            $attachment->import();

            // Attach the thumbnail if attachment is marked as such.
            if ( $attachment->is_thumbnail() ) {
                try {
                    ( new PostThumbnail( $post_importable, $attachment ) )->save();
                }
                catch ( AttributeException $e ) {
                    $this->error_handler->set_error(
                        'An error occurred while saving the post thumbnail. Error: ' . $e->getMessage(),
                    );
                }
            }
        } );

        // Done saving.
        $this->set_save_state( 'attachments' );
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
                catch ( Exception $e ) {
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
                // TODO: Check term type. before running import().
                // Import term if it's not already imported.
                if ( ! $term->is_imported() ) {
                    $term_id = $term->import();
                }

                // Map the ids into the relationship array.
                $term_ids_by_tax[ $term->get_taxonomy() ]   = $term_ids_by_tax[ $term->get_taxonomy() ] ?? [];
                $term_ids_by_tax[ $term->get_taxonomy() ][] = $term_id;
            }
            foreach ( $term_ids_by_tax as $taxonomy => $terms ) {
                // Set terms for the post object.
                wp_set_object_terms( $this->importable->get_wp_id(), $terms, $taxonomy );
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
            catch ( \Exception $exception ) {
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
     * Save the post thumbnail.
     */
    protected function save_thumbnail() {
        try {
            $this->importable->get_thumbnail()->save();
        }
        catch ( AttributeException $e ) {
            $this->error_handler->set_error( $e->getMessage(), $e );
        }
    }

    /**
     * Adds postmeta rows for matching a WP post with the OOPI id.
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
     * @throws RollbackException If the rollback fails, an exception is thrown.
     */
    protected function rollback() {
        // Set the rollback mode.
        $this->rollback_mode = true;

        $last_import = Log::get_last_successful_import( $this->importable->get_wp_id() );

        if ( $last_import && Settings::get( 'rollback_disable' ) !== true ) {
            // First delete all imported data.
            $this->delete_data();

            // Save the previous import again.
            $data = $last_import->get_data();
            $this->importable->set_data( $data );
            try {
                $this->import( $this->importable, $this->error_handler );
                $this->rollback_mode = false;
            }
            catch ( Exception $e ) {
                $this->rollback_mode = false;
                throw new RollbackException( $e->getMessage(), $e->getCode(), $e );
            }

            return true;
        }
        else {
            // Set post status to 'draft' to hide posts containing errors.
            $update_status = [
                'ID'          => $this->importable->get_wp_id(),
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
        Storage::delete_post_meta_data( $this->importable->get_wp_id() );

        // Delete all term relationships.
        \wp_delete_object_term_relationships( $this->importable->get_wp_id(), \get_taxonomies() );

        // Run custom action for custom data.
        // Use this if the data is not in the postmeta table.
        do_action( 'oopi_delete_data', $this->importable->get_wp_id() );
    }
}
