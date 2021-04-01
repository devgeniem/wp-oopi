<?php
/**
 * The Post class is used to import posts into WordPres.
 */

namespace Geniem\Oopi\Importable;

use Geniem\Oopi\Exception\PostException;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importer\PostImporter;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Language;
use Geniem\Oopi\Localization\Controller;
use Geniem\Oopi\Localization\Polylang as Polylang;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Storage;
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
class Post implements Importable {

    /**
     * A unique id for external identification.
     *
     * @var string
     */
    protected $oopi_id;

    /**
     * The importer.
     *
     * @var Importer
     */
    protected $importer;

    /**
     * The error handler.
     *
     * @var ErrorHandler
     */
    protected $error_handler;

    /**
     * If this is an existing posts, the WP id is stored here.
     *
     * @var int
     */
    protected $post_id;

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
    protected $attachments = [];

    /**
     * Holds attachments ids in an associative array
     * after is has been uploaded and saved.
     *
     * @var array $attachment_ids = [
     *      [ oopi_attachment_{$id} => {$post_id} ]
     * ]
     */
    protected $attachment_ids = [];

    /**
     * Metadata in an associative array.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Oopi Term objects in an array.
     *
     * @see $this->set_taxonomies() For description.
     * @var Term[]
     */
    protected $taxonomies = [];

    /**
     * The language data.
     *
     * @var Language
     */
    protected $language;

    /**
     * An array for locale data.
     *
     * @deprecated Use $language instead.
     *
     * @var array
     */
    protected $i18n = [];

    /**
     * An array of Advanced Custom Fields data.
     *
     * @var array
     */
    protected $acf = [];

    /**
     * Set this to true to skip validation and force saving.
     *
     * @var bool
     */
    protected $force_save = false;

    /**
     * Getter for post_id
     *
     * @return integer
     */
    public function get_post_id() {
        return $this->post_id;
    }

    /**
     * Set the post id.
     *
     * @param int $post_id The post_id.
     *
     * @return Post Return self to enable chaining.
     */
    public function set_post_id( int $post_id ): Post {
        $this->post_id = $post_id;

        return $this;
    }

    /**
     * Getter for the post name in the set post data.
     *
     * @return string
     */
    public function get_post_name() {
        return $this->post->post_name ?? '';
    }

    /**
     * Getter for ig_id
     *
     * @return string
     */
    public function get_oopi_id(): string {
        return $this->oopi_id;
    }

    /**
     * Getter for i18n
     *
     * @return array
     */
    public function get_i18n() {
        return $this->i18n;
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
     * @return Term[]
     */
    public function get_taxonomies(): array {
        return $this->taxonomies;
    }

    /**
     * Get the acf.
     *
     * @return array
     */
    public function get_acf(): array {
        return $this->acf;
    }

    /**
     * Get the force_save.
     *
     * @return bool
     */
    public function is_force_save(): bool {
        return $this->force_save;
    }

    /**
     * Set the force_save.
     *
     * @param bool $force_save The force_save.
     *
     * @return Post Return self to enable chaining.
     */
    public function set_force_save( ?bool $force_save ): Post {
        $this->force_save = $force_save;

        return $this;
    }

    /**
     * Getter for the post id.
     *
     * @return int|null
     */
    public function get_wp_id(): ?int {
        return $this->get_post_id();
    }

    /**
     * Setter for importer.
     *
     * @param Importer $importer The importer.
     *
     * @return mixed|void
     */
    public function set_importer( Importer $importer ) {
        $this->importer = $importer;
    }

    /**
     * Getter for importer.
     *
     * @return Importer
     */
    public function get_importer(): Importer {
        return $this->importer;
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
     * The error scope.
     */
    const ESCOPE = 'post';

    /**
     * Post constructor.
     *
     * @param string            $oopi_id       The external id.
     * @param Importer|null     $importer      Optionally, an importer can be set during instantiation.
     * @param ErrorHandler|null $error_handler An optional error handler.
     */
    public function __construct( string $oopi_id, ?Importer $importer = null, ?ErrorHandler $error_handler = null ) {
        // If no error handler is set, use the default one.
        if ( empty( $error_handler ) ) {
            $this->error_handler = new OopiErrorHandler();
        }

        // If no importer set, use the default one.
        if ( empty( $importer ) ) {
            try {
                $this->importer = new PostImporter( $this, $error_handler );
            }
            catch ( TypeException $e ) {
                // This should never happen.
                $this->error_handler->set_error( static::ESCOPE, $e->getTrace(), $e->getMessage() );
            }
        }

        // Set the Importer id.
        $this->oopi_id = $oopi_id;
        // Fetch the WP post id, if it exists.
        $this->set_post_id( Storage::get_post_id_by_oopi_id( $oopi_id ) ?: null );
        if ( $this->post_id ) {
            // Fetch the existing WP post object.
            $this->post = get_post( $this->post_id );
            // Unset the time values to ensure updates.
            unset( $this->post->post_date );
            unset( $this->post->post_date_gmt );
            unset( $this->post->post_modified );
            unset( $this->post->post_modified_gmt );
        }
    }

    /**
     * Handles a full importer object data setting.
     *
     * @param object $data An object following the plugin specification.
     *
     * @return Post By returning self, setters are chainable.
     */
    public function set_data( $data ) : Post {
        $this->set_post( $data->post );

        // Attachments
        if ( isset( $data->attachments ) && is_array( $data->attachments ) ) {
            $this->set_attachments( $data->attachments );
        }

        // Post meta
        if ( isset( $data->meta ) ) {
            $this->set_meta( $data->meta );
        }

        // Taxonomies
        if ( isset( $data->taxonomies ) && is_array( $data->taxonomies ) ) {
            $this->set_taxonomies( $data->taxonomies );
        }

        // Advanced custom fields
        if ( isset( $data->acf ) && is_array( $data->acf ) ) {
            $this->set_acf( $data->acf );
        }

        // If post object has a language object property, set post language.
        if ( isset( $data->language ) ) {
            $this->set_language( $data->language );
        }

        // @deprecated: If post object has i18n object property, set post language
        if ( isset( $data->i18n ) ) {
            $this->set_i18n( $data->i18n );
        }

        return $this;
    }

    /**
     * Sets the basic data of a post.
     *
     * @param  WP_Post $post_obj Post object.
     * @return WP_Post Post object.
     */
    public function set_post( WP_Post $post_obj ) {
        // If the post already exists, update values.
        if ( ! empty( $this->post ) ) {
            foreach ( get_object_vars( $post_obj ) as $attr => $value ) {
                $this->post->{$attr} = $value;
            }
        }
        else {
            // Set the post object.
            $this->post    = new \WP_Post( $post_obj );
            $this->post_id = null;
        }
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
     * @param \WP_Post $post_obj An WP_Post instance.
     */
    public function validate_post( $post_obj ) {
        $e_scope = static::ESCOPE;

        // Validate the author.
        if ( isset( $post_obj->author ) ) {
            $user = \get_userdata( $post_obj->author );
            if ( false === $user ) {
                $err = __( 'Error in the "author" column. The value must be a valid user id.', 'oopi' );
                $this->error_handler->set_error( $e_scope, 'author', $err );
            }
        }

        // Validate date values
        if ( isset( $post_obj->post_date ) ) {
            $this->validate_date( $post_obj->post_date, 'post_date', $e_scope );
        }
        if ( isset( $post_obj->post_date_gmt ) ) {
            $this->validate_date( $post_obj->post_date_gmt, 'post_date_gmt', $e_scope );
        }
        if ( isset( $post_obj->post_modified ) ) {
            $this->validate_date( $post_obj->post_modified, 'post_modified', $e_scope );
        }
        if ( isset( $post_obj->post_modified_gtm ) ) {
            $this->validate_date( $post_obj->post_modified_gtm, 'post_modified_gtm', $e_scope );
        }

        // Validate the post status.
        if ( isset( $post_obj->post_status ) ) {
            $post_statuses = \get_post_statuses();
            if ( 'trash' === $post_obj->post_status ) {
                // @codingStandardsIgnoreStart
                $err = __( 'Error in the "post_status" column. The post is currently trashed, please solve before importing.', 'oopi' );
                // @codingStandardsIgnoreEnd
                $this->error_handler->set_error( $e_scope, 'post_status', $err );
            }
            elseif ( ! array_key_exists( $post_obj->post_status, $post_statuses ) ) {
                // @codingStandardsIgnoreStart
                $err = __( 'Error in the "post_status" column. The value is not a valid post status.', 'oopi' );
                // @codingStandardsIgnoreEnd
                $this->error_handler->set_error( $e_scope, 'post_status', $err );
            }
        }

        // Validate the comment status.
        if ( isset( $post_obj->comment_status ) ) {
            $comment_statuses = [ 'hold', 'approve', 'spam', 'trash', 'open', 'closed' ];
            if ( ! in_array( $post_obj->comment_status, $comment_statuses, true ) ) {
                // @codingStandardsIgnoreStart
                $err = __( 'Error in the "comment_status" column. The value is not a valid comment status.', 'oopi' );
                // @codingStandardsIgnoreEnd
                $this->error_handler->set_error( $e_scope, 'comment_status', $err );
            }
        }

        // Validate the post parent.
        if ( isset( $post_obj->post_parent ) && $post_obj->post_parent !== 0 ) {
            $parent_id = Util::is_query_id( $post_obj->post_parent );
            if ( $parent_id !== false ) {
                // Check if parent exists.
                $parent_post_id = Storage::get_post_id_by_oopi_id( $parent_id );
                if ( $parent_post_id === false ) {
                    $err = __( 'Error in the "post_parent" column. The queried post parent was not found.', 'oopi' ); // phpcs:ignore
                    $this->error_handler->set_error( $e_scope, 'menu_order', $err );
                }
                else {
                    // Set parent post id.
                    $post_obj->post_parent = $parent_post_id;
                }
            }
            else {
                // The parent is a WP post id.
                if ( \get_post( $parent_id ) === null ) {
                    $err = __( 'Error in the "post_parent" column. The parent id did not match any post.', 'oopi' ); // phpcs:ignore
                    $this->error_handler->set_error( $e_scope, 'menu_order', $err );
                }
            }
        }

        // Validate the menu order.
        if ( isset( $post_obj->menu_order ) ) {
            if ( ! is_integer( $post_obj->menu_order ) ) {
                $err = __( 'Error in the "menu_order" column. The value must be an integer.', 'oopi' );
                $this->error_handler->set_error( $e_scope, 'menu_order', $err );
            }
        }

        // Validate the post type.
        if ( isset( $post_obj->post_type ) ) {
            $post_types = get_post_types();
            if ( ! array_key_exists( $post_obj->post_type, $post_types ) ) {
                $err = __( 'Error in the "post_type" column. The value does not match a registered post type.', 'oopi' ); // phpcs:ignore
                $this->error_handler->set_error( $e_scope, 'post_type', $err );
            }
        }
    }

    /**
     * Validate a mysql datetime value.
     *
     * @param string $date_string The datetime string.
     * @param string $col_name    The posts table column name.
     * @param string $err_scope   The error scope name.
     */
    public function validate_date( $date_string = '', $col_name = '', $err_scope = '' ) {
        $valid = \DateTime::createFromFormat( 'Y-m-d H:i:s', $date_string );
        if ( ! $valid ) {
            $err = sprintf(
                // translators: %s stands for the column name.
                __( 'Error in the %s column. The value is not a valid datetime string.', 'oopi' ),
                $col_name
            );
            $this->error_handler->set_error( $err_scope, $col_name, $err );
        }
    }


    /**
     * Set attachment data.
     *
     * @todo validation?
     * @param array $attachments Attachment objects|arrays.
     */
    public function set_attachments( $attachments ) {
        $this->attachments = $attachments;
    }

    /**
     * Sets the post meta data.
     *
     * @param array $meta_data The meta data in an associative array.
     */
    public function set_meta( $meta_data = [] ) {
        // Force type to array.
        $this->meta = (array) $meta_data;
        // Filter values before validating.
        foreach ( $this->meta as $key => $value ) {
            $this->meta[ $key ] = apply_filters( "oopi_meta_value_$key", $value );
        }

        $this->validate_meta( $this->meta );
    }

    /**
     * Validate postmeta.
     *
     * @param array $meta Post meta.
     * @todo Validations and filters.
     */
    public function validate_meta( $meta ) {
        $errors = [];
        if ( ! empty( $errors ) ) {
            $this->error_handler->set_error( 'meta', $errors );
        }
    }

    /**
     * Set the taxonomies of the post.
     *
     * Example of setting a category as a Geniem\Oopi\Term object:
     *     $term = ( new Geniem\Oopi\Term( 'external_id_123' ) )
     *         ->set_name( 'My category' )
     *         ->set_slug( 'my-category' )
     *         ->set_taxonomy( 'cateogory' );
     *     $tax_array = [
     *         $term
     *     ];
     *
     * Example of setting a category as raw data:
     *      $tax_array = [
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
    public function set_taxonomies( ?array $tax_array = [] ) {
        // Filter values before validating.
        foreach ( $tax_array as $term ) {
            if ( ! $term instanceof Term ) {
                $oopi_id = Util::get_prop( $term, 'oopi_id', '' );
                $term    = ( new Term( $oopi_id ) )->set_data( $term );
            }
            $this->taxonomies[] = apply_filters( 'oopi_taxonomy_term', $term );
        }
        $this->validate_taxonomies( $this->taxonomies );
    }

    /**
     * Validate the taxonomy array.
     *
     * @param Term[] $taxonomies The set taxonomies for the post.
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
     * Sets the post acf data.
     *
     * @param array $acf_data The acf data in an associative array.
     */
    public function set_acf( $acf_data = [] ) {
        // Force type to array.
        $this->acf = (array) $acf_data;
        // Filter values before validating.
        /* @todo filtering (by name, not $key?)
        foreach ( $this->acf as $key => $value ) {
            $this->acf[$key] = apply_filters( "oopi_acf_value_$key", $value );
        }
        */
        $this->validate_acf( $this->acf );
    }

    /**
     * Validate acf.
     *
     * @param array $acf Post acf fields.
     * @todo Validations and filters.
     */
    public function validate_acf( $acf ) {
        $errors = [];
        if ( $acf && ! empty( $errors ) ) {
            $this->error_handler->set_error( 'acf', $errors );
        }
    }

    /**
     * Get the language.
     *
     * @return Language
     */
    public function get_language() : Language {
        return $this->language;
    }

    /**
     * Sets the post's language data.
     *
     * @param Language|array|object $language The language data.
     */
    public function set_language( $language ) {
        if ( ! $language instanceof Language ) {
            $this->language = ( new Language() )->set_data( $language );
        }
        else {
            $this->language = $language;
        }
        // TODO: validate the language object.
    }

    /**
     * Sets the post localization data.
     *
     * @deprecated
     *
     * @param array $i18n_data The localization data in an associative array.
     */
    public function set_i18n( $i18n_data ) {
        $this->i18n = $i18n_data;
        $this->validate_i18n( $this->i18n );

        $locale = Util::get_prop( $this->i18n, 'locale' );
        $master = Util::get_prop( $this->i18n, 'master' );

        if ( ! is_scalar( $master ) ) {
            $master = Util::get_prop( $master, 'query_key' );
        }

        $this->language = new Language( $locale, $master );
    }

    /**
     * Validate the locale array.
     *
     * @param array $i18n The set pll data for the post.
     */
    public function validate_i18n( $i18n ) {

        // Check if the polylang plugin is activated.
        if ( Controller::get_activated_i18n_plugin( $this ) === false ) {
            return;
        }

        // Check if locale is set and in the current installation.
        if ( ! Util::get_prop( $i18n, 'locale' ) ) {
            $err = __( 'Error in the polylang data. The locale is not set.', 'oopi' );
            $this->error_handler->set_error( 'i18n', $i18n, $err );
        }
        elseif ( ! in_array( Util::get_prop( $i18n, 'locale' ), Polylang::language_list(), true ) ) {
            $err = __( 'Error in the polylang data. The locale doesn\'t exist in the current WP installation', 'oopi' ); // phpcs:ignore
            $this->error_handler->set_error( 'i18n', $i18n, $err );
        }

        // If a master post is set for the current post, check its validity.
        $master = Util::get_prop( $i18n, 'master', false );
        if ( $master ) {
            if ( Util::is_query_id( Util::get_prop( $master, 'query_key', '' ) ) === false ) {
                $err = __( 'Error in the i18n data. The master query id is missing or invalid.', 'oopi' );
                $this->error_handler->set_error( 'i18n', $i18n, $err );
            }
        }

    }

    /**
     * Stores the post instance and all its data into the database.
     *
     * @throws PostException If the post data is not valid.
     *
     * @return int|null Post id or null if importing fails.
     */
    public function import() : ?int {
        return $this->importer->import();
    }

    /**
     * Checks whether the current post is valid.
     */
    public function validate() : bool {
        return empty( $this->errors );
    }
}
