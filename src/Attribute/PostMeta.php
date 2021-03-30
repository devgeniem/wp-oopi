<?php
/**
 * Attribute
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Interfaces\ErrorHandlerInterface;
use Geniem\Oopi\Importable\Post;

/**
 * Class PostMeta
 *
 * @package Geniem\Oopi\Attribute
 */
class PostMeta extends Meta {

    /**
     * The parent post object.
     *
     * @var Post
     */
    protected $importable;

    /**
     * Save the post meta.
     *
     * @param ErrorHandlerInterface $error_handler The parent's error handler.
     */
    public function save( ErrorHandlerInterface $error_handler ) {

        // Check if post has am attachment thumbnail
        if ( $this->key === '_thumbnail_id' ) {
            // First check if attachments have been saved.
            // If not, set an error and skip thumbnail setting.
            if ( ! $this->importable->is_saved( 'attachments' ) ) {
                // @codingStandardsIgnoreStart
                $err = __( 'Attachments must be saved before saving the thumbnail id for a post. Discarding saving meta for the key "_thumbnail_id".', 'oopi' );
                // @codingStandardsIgnoreEnd
                $error_handler->set_error( 'meta', $this->key, $err );
                return;
            }

            // If attachment id exists
            $attachment_post_id = $this->attachment_ids[ $this->value ] ?? '';

            // If not empty set _thumbnail_id
            if ( ! empty( $attachment_post_id ) ) {
                $this->value = $attachment_post_id;
            }
            // Set error: attachment did not exist.
            else {
                // @codingStandardsIgnoreStart
                $error_handler->set_error( 'meta', $this->key, __( 'Can not save the thumbnail data. The attachment was not found.', 'oopi' ) );
                // @codingStandardsIgnoreEnd
                return;
            }
        }

        // Update post meta
        update_post_meta( $this->importable->get_wp_id(), $this->key, $this->value );
    }

}
