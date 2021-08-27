<?php
/**
 * Attribute saver implementation for post thumbnails.
 */

namespace Geniem\Oopi\Attribute\Saver;

use Geniem\Oopi\Attribute\PostThumbnail;
use Geniem\Oopi\Exception\AttributeException;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;

/**
 * Class PostThumbnailSaver
 *
 * @package Geniem\Oopi\Attribute\Saver
 */
class PostThumbnailSaver implements AttributeSaver {

    /**
     * The save method implementation for post meta attributes.
     *
     * @param Importable $importable A save operation is always related to an importable.
     * @param Attribute  $attribute  A save operation is always related to an attribute.
     *
     * @return int|bool|null Null on type error. False on post meta save error.
     * @throws AttributeException Thrown if there are missing properties in the importable or the attribute.
     */
    public function save( Importable $importable, Attribute $attribute ) {
        if ( ! $attribute instanceof PostThumbnail ) {
            $importable->get_error_handler()->set_error(
                'PostThumbnailSaver can only save post thumbnail attributes. Type given: ' . get_class( $attribute ),
                $attribute
            );
            return null;
        }

        if ( empty( $importable->get_wp_id() ) ) {
            throw new AttributeException( 'Unable to save the post thumbnail. Post id is missing.' );
        }
        if ( empty( $attribute->get_attachment()->get_wp_id() ) ) {
            throw new AttributeException( 'Unable to save the post thumbnail. Attachment id is missing.' );
        }

        return set_post_thumbnail( $importable->get_wp_id(), $attribute->get_attachment()->get_wp_id() );
    }
}
