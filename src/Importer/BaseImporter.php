<?php

namespace Geniem\Oopi\Importer;

use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;

/**
 * Class BaseImporter
 *
 * @package Geniem\Oopi\Importer
 */
abstract class BaseImporter implements Importer {

    /**
     * Holds the importable object.
     *
     * @var Importable
     */
    protected $importable;

    /**
     * ImportableAccessing constructor.
     *
     * @param Importable $importable The importable object.
     */
    public function __construct( Importable $importable ) {
        $this->importable = $importable;
    }

    /**
     * Get the importable.
     *
     * @return Importable
     */
    public function get_importable() : Importable {
        return $this->importable;
    }

}