<?php
/**
 * Attribute factories should implement this interface.
 */

namespace Geniem\Oopi\Interfaces;

use Geniem\Oopi\Exception\TypeException;

/**
 * AttributeFactory
 *
 * @package Geniem\Oopi\Interfaces
 */
interface AttributeFactory {

    /**
     * Creates an attribute for the given importable.
     *
     * @param Importable   $importable The importable object.
     * @param array|object $data       The attribute data.
     *
     * @return Attribute
     * @throws TypeException Thrown if the importable is not a post importable.
     */
    public static function create( Importable $importable, $data ) : Attribute;
}
