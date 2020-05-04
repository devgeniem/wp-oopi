<?php
/**
 * Integrations for RediPress.
 */

namespace Geniem\Oopi\Integration;

use Geniem\Oopi\Post;

/**
 * Class RediPress
 *
 * @package Geniem\Oopi\Integration
 */
class RediPress {

    /**
     * Add hooks for integrations.
     */
    public function integrate() {
        add_action( 'wp_oopi_before_post_save', 1 );
        add_action( 'wp_oopi_after_post_save', 1, 1 );
    }

    /**
     * Run before saving a post.
     */
    public function before_save_post() {
        // Disable writing to disk during an import.
        add_filter( 'redipress/write_to_disk', '__return_false' );
    }

    /**
     * Run after saving a post.
     *
     * @param Post $post Oopi post.
     */
    public function after_save_post( Post $post ) {
        $index = apply_filters( 'redipress/index_instance', null ); // phpcs:ignore
        if ( $index instanceof \Geniem\RediPress\Index\Index ) {
            // Enable writing to disk,
            $index->write_to_disk();
            // Index the post.
            if ( is_int( $post->get_post_id() ) ) {
                do_action( 'redipress/cli/index_single', $post->get_post_id() ); // phpcs:ignore
            }
        }
    }
}
