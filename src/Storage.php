<?php
/**
 * Database handler class.
 */

namespace Geniem\Oopi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Storage
 *
 * @package Geniem\Oopi
 */
class Storage {

    /**
     * Format the storage query key out of a given Oopi id.
     *
     * @param int|string $oopi_id The api id to be matched with postmeta.
     * @return string
     */
    public static function format_query_key( $oopi_id ) {
        return Settings::get( 'id_prefix' ) . $oopi_id;
    }

    /**
     * Query the WP post id by the given Oopi id.
     *
     * @param  int|string $id The api id to be matched with postmeta.
     * @return int|boolean The found post id or 'false' for empty results.
     */
    public static function get_post_id_by_oopi_id( $id ) {
        global $wpdb;
        // Concatenate the meta key.
        $post_meta_key = static::format_query_key( $id );
        // Prepare the sql.
        $prepared = $wpdb->prepare(
            "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = %s",
            $post_meta_key
        );
        // Fetch results from the postmeta table.
        $results = $wpdb->get_col( $prepared ); // phpcs:ignore

        if ( ! empty( $results ) ) {
            return (int) $results[0];
        }

        return false;
    }

    /**
     * Deletes all postmeta related to a single post.
     * Flushes postmeta cache after database rows are deleted.
     *
     * @param int $post_id The WP post id.
     */
    public static function delete_post_meta_data( $post_id ) {
        global $wpdb;

        $query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE post_id = %d", $post_id );
        $wpdb->query( $query ); // phpcs:ignore

        wp_cache_delete( $post_id, 'post_meta' );
    }

    /**
     * Query the WP post id by the given attachment id.
     *
     * @param  int $id     The attachment id to be matched with postmeta.
     * @return int|boolean The found attachment post id or 'false' for empty results.
     */
    public static function get_attachment_post_id_by_attachment_id( $id ) {
        global $wpdb;
        // Concatenate the meta key.
        $post_meta_key = Settings::get( 'attachment_prefix' ) . $id;
        // Prepare the sql.
        $prepared = $wpdb->prepare(
            "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = %s",
            $post_meta_key
        );
        // Fetch results from the postmeta table.
        $results = $wpdb->get_col( $prepared ); // phpcs:ignore

        if ( ! empty( $results ) ) {
            return (int) $results[0];
        }

        return false;
    }

    /**
     * Delete a post by Oopi id
     *
     * @param  int     $oopi_id      Oopi id.
     * @param  boolean $force_delete Set as false, if you wish to trash instead of deleting.
     *
     * @return mixed The post object (if it was deleted or moved to the trash successfully)
     *               or false (failure). If the post was moved to the trash,
     *               the post object represents its new state; if it was deleted,
     *               the post object represents its state before deletion.
     */
    public static function delete_post( $oopi_id, $force_delete = true ) {
        $post_id = self::get_post_id_by_oopi_id( $oopi_id );

        if ( $post_id ) {
            return wp_delete_post( $post_id, $force_delete );
        }

        return false;
    }

    /**
     * Delete all posts
     *
     * @param boolean $force_delete Whether to bypass trash and force deletion.
     * @return void
     */
    public static function delete_all_posts( $force_delete = true ) {
        global $wpdb;
        $id_prefix     = Settings::get( 'id_prefix' );
        $identificator = rtrim( $id_prefix, '_' );
        $query         = "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = %s";
        $results       = $wpdb->get_col( $wpdb->prepare( $query, $identificator ) ); // phpcs:ignore

        if ( ! empty( $results ) ) {
            foreach ( $results as $post_id ) {
                wp_delete_post( $post_id, $force_delete );
            }
        }
    }

    /**
     * Create a new term.
     *
     * @param  array $term Term data.
     * @param  Post  $post The current post instance.
     *
     * @return object|\WP_Error An array containing the `term_id` and `term_taxonomy_id`,
     *                        WP_Error otherwise.
     */
    public static function create_new_term( array $term, Post $post ) {
        $taxonomy = $term['taxonomy'];
        $name     = $term['name'];
        $slug     = $term['slug'];
        // There might be a parent set.
        $parent = isset( $term['parent'] ) ? get_term_by( 'slug', $term['parent'], $taxonomy ) : false;
        // Insert the new term.
        $result = wp_insert_term( $name, $taxonomy, [
            'slug'        => $slug,
            'description' => isset( $term['description'] ) ? $term['description'] : '',
            'parent'      => $parent ? $parent->term_id : 0,
        ] );
        // Something went wrong.
        if ( is_wp_error( $result ) ) {
            $err = __( 'An error occurred creating the taxonomy term.', 'oopi' );
            $post->set_error( 'taxonomy', $name, $err );
            return $result;
        }

        return (object) $result;
    }
}
