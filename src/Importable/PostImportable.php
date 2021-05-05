<?php
/**
 * The post importable.
 */

namespace Geniem\Oopi\Importable;

use Geniem\Oopi\Attribute\AcfField;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Factory\AcfFieldFactory;
use Geniem\Oopi\Importer\PostImporter;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Storage;
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
     * Use post meta fetaures.
     */
    use HasPostMeta;

    /**
     * Use the language attribute.
     */
    use Translatable;

    /**
     * A unique id for external identification.
     *
     * @var string
     */
    protected string $oopi_id;

    /**
     * The error handler.
     *
     * @var ErrorHandler
     */
    protected ErrorHandler $error_handler;

    /**
     * If this is an existing posts, the WP id is stored here.
     *
     * @var int|null
     */
    protected ?int $wp_id;

    /**
     * An object resembling the WP_Post class instance.
     *
     * @var object The post data object.
     */
    protected $post;

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
        $this->set_wp_id( Storage::get_post_id_by_oopi_id( $oopi_id ) ?: null );
        if ( $this->wp_id ) {
            // Fetch the existing WP post object.
            $this->post = get_post( $this->wp_id );
            // Unset the time values to ensure updates.
            unset( $this->post->post_date );
            unset( $this->post->post_date_gmt );
            unset( $this->post->post_modified );
            unset( $this->post->post_modified_gmt );
        }
    }

    /**
     * Sets the basic data of a post.
     *
     * @param  WP_Post $post_obj Post object.
     * @return WP_Post Post object.
     */
    public function set_post( WP_Post $post_obj ) {
        // Set the WP id.
        $this->set_wp_id( $this->post->ID );
        // Set the post object.
        $this->post = new WP_Post( $post_obj );

        // Filter values before validating.
        foreach ( get_object_vars( $this->post ) as $attr => $value ) {
            $this->post->{$attr} = apply_filters( "oopi_post_value_{$attr}", $value );
        }
        // Validate it.
        $this->validate_post( $this->post );

        return $this->post;
    }

    /**
     * Return the related WP post object.
     *
     * @return WP_Post|null
     */
    public function get_post() : ?WP_Post {
        return $this->post;
    }

    /**
     * Validates the post object data.
     *
     * @param WP_Post $post_obj An WP_Post instance.
     */
    public function validate_post( $post_obj ) {
        // Validate the author.
        if ( isset( $post_obj->author ) ) {
            $user = \get_userdata( $post_obj->author );
            if ( false === $user ) {
                $err = 'Error in the "author" column. The value must be a valid user id.';
                $this->error_handler->set_error( $err );
            }
        }

        // Validate date values
        if ( isset( $post_obj->post_date ) ) {
            $this->validate_date( $post_obj->post_date, 'post_date' );
        }
        if ( isset( $post_obj->post_date_gmt ) ) {
            $this->validate_date( $post_obj->post_date_gmt, 'post_date_gmt' );
        }
        if ( isset( $post_obj->post_modified ) ) {
            $this->validate_date( $post_obj->post_modified, 'post_modified' );
        }
        if ( isset( $post_obj->post_modified_gtm ) ) {
            $this->validate_date( $post_obj->post_modified_gtm, 'post_modified_gtm' );
        }

        // Validate the post status.
        if ( isset( $post_obj->post_status ) ) {
            $post_statuses = \get_post_statuses();
            if ( 'trash' === $post_obj->post_status ) {
                $err = 'Error in the "post_status" column. 
                The post is currently trashed, please solve before importing.';
                $this->error_handler->set_error( $err );
            }
            if ( ! array_key_exists( $post_obj->post_status, $post_statuses ) ) {
                $err = 'Error in the "post_status" column. The value is not a valid post status.';
                $this->error_handler->set_error( $err );
            }
        }

        // Validate the comment status.
        if ( isset( $post_obj->comment_status ) ) {
            $comment_statuses = [ 'hold', 'approve', 'spam', 'trash', 'open', 'closed' ];
            if ( ! in_array( $post_obj->comment_status, $comment_statuses, true ) ) {
                $err = 'Error in the "comment_status" column. The value is not a valid comment status.';
                $this->error_handler->set_error( $err );
            }
        }

        // Validate the post parent.
        if ( isset( $post_obj->post_parent ) && $post_obj->post_parent !== 0 ) {
            $parent_id = Util::is_query_id( $post_obj->post_parent );
            if ( $parent_id !== false ) {
                // Check if parent exists.
                $parent_post_id = Storage::get_post_id_by_oopi_id( $parent_id );
                if ( $parent_post_id === false ) {
                    $err = 'Error in the "post_parent" column. The queried post parent was not found.'; // phpcs:ignore
                    $this->error_handler->set_error( $err );
                }
                else {
                    // Set parent post id.
                    $post_obj->post_parent = $parent_post_id;
                }
            }
            else {
                // The parent is a WP post id.
                if ( \get_post( $parent_id ) === null ) {
                    $err = 'Error in the "post_parent" column. The parent id did not match any post.'; // phpcs:ignore
                    $this->error_handler->set_error( $err );
                }
            }
        }

        // Validate the menu order.
        if ( isset( $post_obj->menu_order ) ) {
            if ( ! is_integer( $post_obj->menu_order ) ) {
                $err = 'Error in the "menu_order" column. The value must be an integer.';
                $this->error_handler->set_error( $err );
            }
        }

        // Validate the post type.
        if ( isset( $post_obj->post_type ) ) {
            $post_types = get_post_types();
            if ( ! array_key_exists( $post_obj->post_type, $post_types ) ) {
                $err = 'Error in the "post_type" column. The value does not match a registered post type.'; // phpcs:ignore
                $this->error_handler->set_error( $err );
            }
        }
    }

    /**
     * Validate a mysql datetime value.
     *
     * @param string $date_string The datetime string.
     * @param string $col_name    The posts table column name.
     */
    public function validate_date( $date_string = '', $col_name = '' ) {
        $valid = \DateTime::createFromFormat( 'Y-m-d H:i:s', $date_string );
        if ( ! $valid ) {
            $err = sprintf(
                'Error in the %s column. The value is not a valid datetime string.',
                $col_name
            );
            $this->error_handler->set_error( $col_name, $err );
        }
    }


    /**
     * Set attachment data.
     *
     * @param array $attachments Attachment objects|arrays.
     */
    public function set_attachments( array $attachments ) {
        // TODO: cast to attachment importables.
        $this->attachments = $attachments;
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
        $this->validate_taxonomies( $this->terms );
    }

    /**
     * Validate the taxonomy array.
     *
     * @param TermImportable[] $taxonomies The set taxonomies for the post.
     */
    public function validate_taxonomies( $taxonomies ) {
        if ( ! is_array( $taxonomies ) ) {
            $err = __( 'Error in the taxonomies. Taxonomies must be passed in an array.', 'oopi' ); // phpcs:ignore
            $this->error_handler->set_error( 'taxonomy', $taxonomies, $err );
            return;
        }

        // The passed taxonomies must be currently registered.
        $registered_taxonomies = \get_taxonomies();
        foreach ( $taxonomies as $term ) {
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
            apply_filters( 'oopi_validate_taxonomies', $taxonomies );
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
