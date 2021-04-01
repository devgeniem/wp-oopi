<?php
/**
 * Attribute saver implementation for post meta attributes.
 */

namespace Geniem\Oopi\Attribute\Saver;

use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;

/**
 * Class PostMetaAttributeSaver
 *
 * @package Geniem\Oopi\Attribute\Saver
 */
class PostMetaSaver implements \Geniem\Oopi\Interfaces\AttributeSaver {

    /**
     * The save method implementation for post meta attributes.
     *
     * @param Importable        $importable    The post importable.
     * @param Attribute         $attribute     The post meta attribute.
     * @param ErrorHandler|null $error_handler An optional error handler.
     *
     * @return int|bool|null
     */
    public function save( Importable $importable, Attribute $attribute, ?ErrorHandler $error_handler = null ) {
        $key   = $attribute->get_key();
        $value = $attribute->get_value();

        // Check if post has am attachment thumbnail
        if ( $key === '_thumbnail_id' ) {
            // First check if attachments have been saved.
            // If not, set an error and skip thumbnail setting.
            if ( ! $importable->is_saved( 'attachments' ) ) {
                // @codingStandardsIgnoreStart
                $err = __( 'Attachments must be saved before saving the thumbnail id for a post. Discarding saving meta for the key "_thumbnail_id".', 'oopi' );
                // @codingStandardsIgnoreEnd
                $error_handler->set_error( 'meta', $key, $err );
                return null;
            }

            // If attachment id exists
            $attachment_post_id = $this->attachment_ids[ $value ] ?? '';

            // If not empty set _thumbnail_id
            if ( ! empty( $attachment_post_id ) ) {
                $value = $attachment_post_id;
            }
            // Set error: attachment did not exist.
            else {
                // @codingStandardsIgnoreStart
                $error_handler->set_error( 'meta', $key, __( 'Can not save the thumbnail data. The attachment was not found.', 'oopi' ) );
                // @codingStandardsIgnoreEnd
                return null;
            }
        }

        // Update post meta
        return update_post_meta( $importable->get_wp_id(), $key, $value );
    }
}
