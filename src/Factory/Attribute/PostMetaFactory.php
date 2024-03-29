<?php
/**
 * A factory for creating post meta attributes statically.
 */

namespace Geniem\Oopi\Factory\Attribute;

use Geniem\Oopi\Attribute\Meta;
use Geniem\Oopi\Attribute\PostMeta;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Interfaces\AttributeFactory;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Util;

/**
 * Class PostMetaFactory
 *
 * @package Geniem\Oopi\Factory
 */
class PostMetaFactory implements AttributeFactory {

    /**
     * Factory method for creating the object from raw data.
     *
     * @param Importable   $importable The importable object.
     * @param array|object $data       The attribute data.
     *
     * @return Meta
     * @throws TypeException Thrown if the importable is not a post importable.
     */
    public static function create( Importable $importable, $data ) : Meta {
        $key   = Util::get_prop( $data, 'key' );
        $value = Util::get_prop( $data, 'value' );
        $saver = Util::get_prop( $data, 'saver', null );

        return new PostMeta( $importable, $key, $value, $saver );
    }
}
