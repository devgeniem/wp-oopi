<?php
/**
 * A saver saves an object and return the created id.
 */

namespace Geniem\Oopi\Interfaces;

/**
 * AttributeSaver
 *
 * @package Geniem\Oopi\Interfaces
 */
interface AttributeSaver {

    /**
     * Saves an object and returns the id of the saved attribute.
     *
     * @param Importable $importable A save operation is always related to an importable.
     * @param Attribute  $attribute  A save operation is always related to an attribute.
     *
     * @return mixed|null Return value is optional.
     */
    public function save( Importable $importable, Attribute $attribute );

}
