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
     * Import the object into WordPress.
     *
     * @param Importable        $importable    The object to be imported.
     * @param ErrorHandler|null $error_handler An optional error handler.
     *
     * @return int|null On success, the WP item id should be returned, null on failure.
     */
    public function import( Importable $importable, ?ErrorHandler $error_handler = null ) : ?int;

    /**
     * Identify an importable with the OOPI id.
     * When called the first time, the id should be stored in the database.
     * This should be done in the beginning of the first import of an object.
     */
    public function identify();

}
