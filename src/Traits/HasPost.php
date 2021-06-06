<?php
/**
 * This trait provides the WP post object property.
 */

namespace Geniem\Oopi\Traits;

use Geniem\Oopi\Storage;
use Geniem\Oopi\Util;
use WP_Post;

/**
 * Trait HasPost
 *
 * @package Geniem\Oopi\Traits
 */
trait HasPost {

    /**
     * The related WP post object.
     *
     * @var WP_Post The post data object.
     */
    protected $post;

    /**
     * Sets the basic data of a post.
     *
     * @param  WP_Post|array|object $post_obj        Post object.
     * @param  bool                 $skip_validation Whether to skip validation or not.
     * @return WP_Post Post object.
     */
    public function set_post( $post_obj, $skip_validation = false ) {
        // Hold onto the current id.
        $current_id = $this->get_wp_id();

        // Set the WP id if found and none is set.
        if ( ! $current_id && Util::get_prop( $post_obj, 'ID' ) ) {
            $this->set_wp_id( Util::get_prop( $post_obj, 'ID' ) );
        }

        // Set the post object.
        if ( $post_obj instanceof WP_Post ) {
            $this->post = $post_obj;
        }
        else {
            $this->post = new WP_Post( (object) $post_obj );
        }

        // Filter values before validating.
        foreach ( get_object_vars( $this->post ) as $attr => $value ) {
            $this->post->{$attr} = apply_filters( "oopi_post_value_{$attr}", $value );
        }

        if ( ! $skip_validation ) {
            // Validate it.
            $this->validate_post( $this->post );
        }

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
            $post_statuses = [
                ...array_keys( \get_post_statuses() ),
                'inherit',
            ];
            if ( 'trash' === $post_obj->post_status ) {
                $err = 'Error in the "post_status" column. 
                The post is currently trashed, please solve before importing.';
                $this->error_handler->set_error( $err );
            }
            if ( ! in_array( $post_obj->post_status, $post_statuses, true ) ) {
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
            $parent_id = Util::get_query_id( $post_obj->post_parent );
            if ( $parent_id ) {
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
                if ( \get_post( $post_obj->post_parent ) === null ) {
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
        Util::validate_date( $this->get_error_handler(), $date_string, $col_name );
    }
}
