<?php
/**
 * Importables are data items that can be imported into WordPress.
 */

namespace Geniem\Oopi\Interfaces;

/**
 * Interface Importable
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
     * @param string $oopi_id A unique id for the importable.
     */
    public function __construct( string $oopi_id );

    /**
     * Setter for data.
     *
     * @param mixed $data The data.
     */
    public function set_data( $data );

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
     * Validates an importable.
     * If validation fails, false is returned and the errors are
     * passed to the error handler.
     *
     * @param ErrorHandlerInterface $error_hander An error handler must be passed for validations.
     * @return bool True on success, false on error.
     */
    public function validate( ErrorHandlerInterface $error_hander ) : bool;

    /**
     * Set an instance specific non-global importer.
     *
     * @param Importer $importer An importer instance. Overrides the global importer.
     *
     * @return mixed
     */
    public function set_importer( Importer $importer );

    /**
     * Getter for the import handler.
     *
     * @return Importer
     */
    public function get_importer() : Importer;
}
