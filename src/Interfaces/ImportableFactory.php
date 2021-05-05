<?php
/**
 * Importable object factories should implement this interface.
 */

namespace Geniem\Oopi\Interfaces;

use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Util;

/**
 * ImportableFactory
 *
 * @package Geniem\Oopi\Interfaces
 */
interface ImportableFactory {

    /**
     * Creates an importable object from the passed data.
     *
     * @param string $oopi_id A unique id for the importable.
     * @param mixed  $data    The importable data.
     *
     * @return Importable
     */
    public static function create( string $oopi_id, $data ) : Importable;
}
