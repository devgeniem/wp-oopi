<?php
/**
 * ImportHandler are used to handle the actual importing
 * of different importable types.
 */

namespace Geniem\Oopi\Interfaces;

/**
 * Interface ImportHandler
 *
 * @property Importable $importable Holds the importable object passed for the handler.
 *
 * @package Geniem\Oopi\Interfaces
 */
interface Importer {

    /**
     * ImportHandler constructor.
     *
     * The importable should be stored as an object property.
     *
     * @param Importable        $object        The object to be imported.
     * @param ErrorHandler|null $error_handler An optional error handler.
     */
    public function __construct( Importable $object, ?ErrorHandler $error_handler = null );

    /**
     * Import the object into WordPress.
     *
     * @return int|null On success, the WP item id should be returned, null on failure.
     */
    public function import() : ?int;

    /**
     * Identify an importable with the OOPI id.
     *
     * @return void
     */
    public function identify();

}
