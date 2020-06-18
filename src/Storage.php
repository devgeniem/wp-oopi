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
        $id_prefix = Settings::get( 'id_prefix' );

        if ( strpos( $oopi_id, $id_prefix ) !== 0 ) {
            return Settings::get( 'id_prefix' ) . $oopi_id;
        }

        return $oopi_id;
    }

    /**
     * Get the Oopi identificator key.
     *
     * @return string
     */
    public static function get_idenfiticator() : string {
        $id_prefix = Settings::get( 'id_prefix' );

        // Remove the trailing '_'.
        return rtrim( $id_prefix, '_' );
    }

    /**
     * Query the WP post id by the given Oopi id.
     *
     * @param  int|string $id The Oopi id to be matched with postmeta.
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
     * Query the WP term id by the given Oopi id.
     *
     * @param  int|string $id The Oopi id to be matched with termmeta.
     * @return int|boolean The found term id or 'false' for empty results.
     */
    public static function get_term_id_by_oopi_id( $id ) {
        global $wpdb;
        // Concatenate the meta key.
        $term_meta_key = static::format_query_key( $id );
        // Prepare the sql.
        $prepared = $wpdb->prepare(
            "SELECT DISTINCT term_id FROM $wpdb->termmeta WHERE meta_key = %s",
            $term_meta_key
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
     * Fetch a WP term by its slug.
     *
     * @param string $slug     The term slug.
     * @param string $taxonomy The taxonomy slug.
     * @return \WP_Term|null A term object or null if none found.
     */
    public static function get_term_by_slug( string $slug, string $taxonomy ) : ?\WP_Term {
        $terms = get_terms(
            [
                'slug'            => $slug,
                'get'             => 'all',
                'number'          => 1,
                'taxonomy'        => $taxonomy,
                'suppress_filter' => true, // No filtering allowed to get raw objects.
                'lang'            => '', // For Polylang, ignores language filters.
            ]
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return null;
        }

        return $terms[0];
    }

    /**
     * Create a new term.
     *
     * @param  Term $term Term data.
     * @param  Post $post The current Oopi post instance.
     *
     * @return object|\WP_Error An array containing the `term_id` and `term_taxonomy_id`,
     *                        WP_Error otherwise.
     */
    public static function create_new_term( Term $term, Post $post ) {
        $taxonomy = $term['taxonomy'] ?? '';
        $name     = $term['name'] ?? '';
        $slug     = $term['slug'] ?? '';

        // If parent's Oopi id is set, fetch it. Default to 0.
        $parent    = $term->get_parent();
        $parent_id = $parent ? static::get_term_id_by_oopi_id( $parent ) : 0;

        // Insert the new term.
        $result = wp_insert_term(
            $name,
            $taxonomy,
            [
                'slug'        => $slug,
                'description' => isset( $term['description'] ) ? $term['description'] : '',
                'parent'      => $parent_id,
            ]
        );
        // Something went wrong.
        if ( is_wp_error( $result ) ) {
            $err = __( 'An error occurred creating the taxonomy term.', 'oopi' );
            $post->set_error( 'taxonomy', $name, $err );
            return $result;
        }

        // Identify the Oopi term.
        $wp_term = get_term( $result['term_id'] );
        $term->set_term( $wp_term );
        $term->identify();

        return (object) $result;
    }
}
