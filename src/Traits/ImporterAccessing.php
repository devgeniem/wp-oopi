<?php
/**
 * This trait can be used to add the required accessors for an importable's importer instance.
 */

namespace Geniem\Oopi\Traits;

use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Plugin;

/**
 * Trait ImporterAccessing
 *
 * @package Geniem\Oopi\Traits
 */
trait ImporterAccessing {

    /**
     * The importer instance.
     *
     * @var Importer
     */
    protected $importer;

    /**
     * Setter for a local importer.
     *
     * @param Importer $importer The instance.
     */
    public function set_importer( Importer $importer ) {
        $this->importer = $importer;
    }

    /**
     * Get the object specific importer or the global one if local is not set.
     *
     * @return Importer The local or global importer.
     */
    public function get_importer() : Importer {
        return $this->importer ?: Plugin::get_importer( get_called_class() );
    }

}
