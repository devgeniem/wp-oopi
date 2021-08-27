<?php
/**
 * Attribute saver implementation for post meta attributes.
 */

namespace Geniem\Oopi\Attribute\Saver;

use Geniem\Oopi\Attribute\PostMeta;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;

/**
 * Class PostMetaSaver
 *
 * @package Geniem\Oopi\Attribute\Saver
 */
class PostMetaSaver implements AttributeSaver {

    /**
     * The save method implementation for post meta attributes.
     *
     * @param Importable $importable A save operation is always related to an importable.
     * @param Attribute  $attribute  A save operation is always related to an attribute.
     *
     * @return int|bool|null Null on type error. False on post meta save error.
     */
    public function save( Importable $importable, Attribute $attribute ) {
        if ( ! $attribute instanceof PostMeta ) {
            $importable->get_error_handler()->set_error(
                'The post meta saver can only save post meta attributes. Type given: ' . get_class( $attribute ),
                $attribute
            );
            return null;
        }

        $key   = $attribute->get_key();
        $value = $attribute->get_value();

        return update_post_meta( $importable->get_wp_id(), $key, $value );
    }
}
