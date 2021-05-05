<?php
/**
 * Importables are data items that can be imported into WordPress.
 */

namespace Geniem\Oopi\Interfaces;

/**
 * Importable
 *
 * @property mixed  $data    The importable data.
 * @property string $oopi_id The OOPI id.
 * @property int    $wp_id   The WordPress object id.
 *
 * @package Geniem\Oopi\Interfaces
 */
interface Importable {

    /**
     * All importables should be initialized with an OOPI id.
     *
     * @param string            $oopi_id       A unique id for the importable.
     * @param Importer|null     $importer      The importer.
     * @param ErrorHandler|null $error_handler An optional error handler.
     */
    public function __construct(
        string $oopi_id,
        ?Importer $importer = null,
        ?ErrorHandler $error_handler = null
    );

    /**
     * Getter for OOPI id.
     *
     * @return string
     */
    public function get_oopi_id() : string;

    /**
     * Getter for the WordPress object id.
     *
     * @return int|null
     */
    public function get_wp_id() : ?int;

    /**
     * Getter for the importer.
     *
     * @return Importer
     */
    public function get_importer() : Importer;

    /**
     * Getter for the error handler.
     *
     * @return ErrorHandler
     */
    public function get_error_handler() : ErrorHandler;

    /**
     * Check if the importable is already imported.
     *
     * @return bool
     */
    public function is_imported() : bool;

    /**
     * Validates an importable.
     *
     * @return bool True on success, false on error.
     */
    public function validate() : bool;

    /**
     * Import the importable with the attached importer.
     *
     * @return int|null
     */
    public function import() : ?int;

}
