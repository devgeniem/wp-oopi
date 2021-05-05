<?php
/**
 * A factory for creating ACF field attributes statically.
 */

namespace Geniem\Oopi\Factory;

use Geniem\Oopi\Attribute\AcfField;
use Geniem\Oopi\Interfaces\AttributeFactory;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Util;

/**
 * Class AcfFieldFactory
 *
 * @package Geniem\Oopi\Factory
 */
class AcfFieldFactory implements AttributeFactory {

    /**
     * Factory method for creating the object from raw data.
     *
     * @param Importable   $importable The importable object.
     * @param array|object $data       The attribute data.
     *
     * @return AcfField
     */
    public static function create( Importable $importable, $data ) : AcfField {
        $key   = Util::get_prop( $data, 'key' );
        $type  = Util::get_prop( $data, 'type' );
        $value = Util::get_prop( $data, 'value' );
        $saver = Util::get_prop( $data, 'saver', null );

        return new AcfField( $importable, $key, $type, $value, $saver );
    }
}
