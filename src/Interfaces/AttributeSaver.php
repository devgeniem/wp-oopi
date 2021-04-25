<?php
/**
 * A saver saves an object and return the created id.
 */

namespace Geniem\Oopi\Interfaces;

use Geniem\Oopi\Exception\AttributeSaveException;

/**
 * Interface Saver
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
     * @throws AttributeSaveException An error should be thrown for erroneous saves.
     *
     * @return mixed|null Return value is optional.
     */
    public function save( Importable $importable, Attribute $attribute );

}
