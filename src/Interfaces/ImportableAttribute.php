<?php

namespace Geniem\Oopi\Interfaces;

/**
 * Interface ImportableAttribute
 *
 * @property Importable $importable All attributes must have a parent importable.
 *                                  For example a post meta attribute has an importable post object.
 * @property ErrorHandlerInterface $error_handler All attributes must have an error handler
 *                                       passed from the parent importable.
 *
 * @package Geniem\Oopi\Interfaces
 */
interface ImportableAttribute {

    /**
     * Setter for the parent object.
     *
     * @param Importable $importable The parent importable.
     */
    public function set_importable( Importable $importable );

    /**
     * Saves the attribute for the importable.
     *
     * @param ErrorHandlerInterface $error_handler The parent's error handler.
     */
    public function save( ErrorHandlerInterface $error_handler );

}
