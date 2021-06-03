<?php
/**
 * The post importable.
 */

namespace Geniem\Oopi\Importable;

use Geniem\Oopi\Attribute\AcfField;
use Geniem\Oopi\Attribute\PostThumbnail;
use Geniem\Oopi\Factory\Importable\AttachmentFactory;
use Geniem\Oopi\Factory\Attribute\AcfFieldFactory;
use Geniem\Oopi\Importer\PostImporter;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Storage;
use Geniem\Oopi\Traits\HasPost;
use Geniem\Oopi\Traits\HasPostMeta;
use Geniem\Oopi\Traits\ImportableBase;
use Geniem\Oopi\Traits\Translatable;
use Geniem\Oopi\Util;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Post
 *
 * @package Geniem\Oopi
 */
class PostImportable implements Importable {

    /**
     * The error scope.
     */
    const ESCOPE = 'post';

    /**
     * Use basic functionalities.
     */
    use ImportableBase;

    /**
     * Use the WP post property.
     */
    use HasPost;

    /**
     * Use post meta fetaures.
     */
    use HasPostMeta;

    /**
     * Use the language attribute.
     */
    use Translatable;

    /**
     * Attachments in an indexed array.
     *
     * @var array
     */
    protected array $attachments = [];

    /**
     * Holds the attachment ids with OOPI id
     * as the key and WP id as the value.
     *
     * @var array
     */
    protected array $attachment_ids = [];

    /**
     * The post thumbnail attribute.
     *
     * @var Attribute|null
     */
    protected $thumbnail = null;

    /**
     * Oopi Term objects in an array.
     *
     * @var TermImportable[]
     */
    protected array $terms = [];

    /**
     * An array of Advanced Custom Fields data.
     *
     * @var AcfField[]
     */
    protected array $acf = [];

    /**
     * Getter for the post name in the set post data.
     *
     * @return string
     */
    public function get_post_name() {
        return $this->post->post_name ?? '';
    }

    /**
     * Get the attachments.
     *
     * @return array
     */
    public function get_attachments(): array {
        return $this->attachments;
    }

    /**
     * Get the attachment_ids.
     *
     * @return array
     */
    public function get_attachment_ids(): array {
        return $this->attachment_ids;
    }

    /**
     * Map the OOPI id and WP id for a post attachment.
     *
     * @param string $oopi_id The OOPI id of the attachment.
     * @param int    $wp_id   The WP id of the attachment.
     */
    public function map_attachment_id( string $oopi_id, int $wp_id ) {
        $this->attachments[ $oopi_id ] = $wp_id;
    }

    /**
     * Get the meta.
     *
     * @return array
     */
    public function get_meta(): array {
        return $this->meta;
    }

    /**
     * Get the taxonomies.
     *
     * @return TermImportable[]
     */
    public function get_terms(): array {
        return $this->terms;
    }

    /**
     * Get the acf.
     *
     * @return AcfField[]
     */
    public function get_acf() : array {
        return $this->acf;
    }

    /**
     * Encode an instance into JSON.
     *
     * @return string|false The JSON encoded string, or false if it cannot be encoded.
     */
    public function to_json() {
        return wp_json_encode( get_object_vars( $this ) );
    }

    /**
     * Post constructor.
     *
     * @param string            $oopi_id       A unique id for the importable.
     * @param Importer|null     $importer      The importer.
     * @param ErrorHandler|null $error_handler An optional error handler.
     */
    public function __construct( string $oopi_id, ?Importer $importer = null, ?ErrorHandler $error_handler = null ) {
        // If no error handler is set, use the default one.
        $this->error_handler = $error_handler ?? new OopiErrorHandler( static::ESCOPE );

        $this->importer = $importer ?? new PostImporter();

        // Set the Importer id.
        $this->oopi_id = $oopi_id;
        // Fetch the WP post id, if it exists.
        $wp_id = Storage::get_post_id_by_oopi_id( $oopi_id );
        if ( $wp_id ) {
            // Fetch and set the existing post object.
            $this->set_post( get_post( $wp_id ), true );
            // Unset the time values to ensure updates.
            unset( $this->post->post_date );
            unset( $this->post->post_date_gmt );
            unset( $this->post->post_modified );
            unset( $this->post->post_modified_gmt );
        }
    }

    /**
     * Set attachment data.
     *
     * @param array $attachments Attachment objects|arrays.
     */
    public function set_attachments( array $attachments ) {
        $importables = array_map(
            function( $attachment ) {
                if ( $attachment instanceof AttachmentImportable ) {
                    return $attachment;
                }

                $oopi_id = Util::get_prop( $attachment, 'oopi_id', null );

                // Use the current error handler if none is set.
                if ( ! Util::get_prop( $attachment, 'error_handler' ) ) {
                    Util::set_prop( $attachment, 'error_handler', $this->get_error_handler() );
                }

                // Set the current OOPI and WP ids to attach the file for this post.
                if ( $this->get_wp_id() ) {
                    Util::set_prop( $attachment, 'parent_wp_id', $this->get_wp_id() );
                }
                Util::set_prop( $attachment, 'parent_oopi_id', $this->get_oopi_id() );

                return AttachmentFactory::create( $oopi_id, $attachment );
            },
            $attachments
        );

        $this->attachments = $importables;
    }

    /**
     * Set the post thumbnail.
     *
     * @param Attribute $thumbnail The thumbnail attribute.
     */
    public function set_thumbnail( Attribute $thumbnail ) {
        $this->thumbnail = $thumbnail;
    }

    /**
     * Get the thumbnail.
     *
     * @return Attribute|null
     */
    public function get_thumbnail(): ?Attribute {
        return $this->thumbnail;
    }

    /**
     * Set the taxonomies of the post.
     *
     * Example of setting a category as a Geniem\Oopi\Importable\Term object:
     *     $term = ( new Geniem\Oopi\Importable\Term( 'external_id_123' ) )
     *         ->set_name( 'My category' )
     *         ->set_slug( 'my-category' )
     *         ->set_taxonomy( 'cateogory' );
     *     $term_array = [
     *         $term
     *     ];
     *
     * Example of setting a category as raw data:
     *      $term_array = [
     *          [
     *              'taxonomy => 'category',
     *              'name'    => 'My category',
     *              'slug'    => 'my-category',
     *              'oopi_id' => 'external_id_123',
     *          ],
     *      ];
     *
     * @param array|null $tax_array The taxonomy and term array.
     */
    public function set_terms( ?array $tax_array = [] ) {
        // Filter values before validating.
        foreach ( $tax_array as $term ) {
            if ( ! $term instanceof TermImportable ) {
                $oopi_id       = Util::get_prop( $term, 'oopi_id', null );
                $error_handler = Util::get_prop( $term, 'error_handler', $this->error_handler );
                $importer      = Util::get_prop( $term, 'importer', null );
                try {
                    $term          = ( new TermImportable( $oopi_id, $importer, $error_handler ) )->set_data( $term );
                    $this->terms[] = apply_filters( 'oopi_taxonomy_term', $term );
                }
                catch ( \Exception $exception ) {
                    $this->error_handler->set_error(
                        'Unable to cast the term to an importable. 
                        OOPI id: ' . $oopi_id . '. Error: ' . $exception->getMessage(),
                        $exception
                    );
                }
                continue;
            }
            $this->terms[] = apply_filters( 'oopi_taxonomy_term', $term );
        }
        $this->validate_terms( $this->terms );
    }

    /**
     * Validate the terms array.
     *
     * @param TermImportable[] $terms The set taxonomies for the post.
     */
    public function validate_terms( $terms ) {
        if ( ! is_array( $terms ) ) {
            $err = __( 'Error in the taxonomies. Taxonomies must be passed in an array.', 'oopi' ); // phpcs:ignore
            $this->error_handler->set_error( 'taxonomy', $terms, $err );
            return;
        }

        // The passed taxonomies must be currently registered.
        $registered_taxonomies = \get_taxonomies();
        foreach ( $terms as $term ) {
            if (
                empty( $term->get_taxonomy() ) ||
                ! in_array( $term->get_taxonomy(), $registered_taxonomies, true )
            ) {
                $err = sprintf(
                    // translators: %s stands for the taxonomy slug.
                    __( 'Error in the %s taxonomy. The taxonomy is not registerd.', 'oopi' ),
                    $term->get_taxonomy()
                );
                $this->error_handler->set_error( 'taxonomy', $term, $err );
            }
            if ( empty( $term->get_oopi_id() ) ) {
                $err = __( 'Error in a term. Required `oopi_id` property not set.', 'oopi' );
                $this->error_handler->set_error( 'taxonomy', $term, $err );
            }
            apply_filters( 'oopi_validate_taxonomies', $terms );
        }
    }

    /**
     * Sets the post ACF data.
     *
     * @param array $acf_data The ACF data in an associative array.
     */
    public function set_acf( array $acf_data = [] ) {
        // Cast to AcfField objects.
        $this->acf = array_filter( array_map( function( $field ) {
            if ( $field instanceof AcfField ) {
                return $field;
            }

            try {
                return AcfFieldFactory::create( $this, $field );
            }
            catch ( \Exception $e ) {
                $this->error_handler->set_error(
                    'Unable to create the post meta attribute. Error: ' . $e->getMessage(),
                    $field
                );
            }
            return null;
        }, $acf_data ) );
    }
}
