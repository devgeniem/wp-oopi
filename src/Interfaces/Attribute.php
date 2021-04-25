<?php
/**
 * Attributes are meta data for importables.
 */

namespace Geniem\Oopi\Interfaces;

use Geniem\Oopi\Exception\AttributeValidationException;

/**
 * Interface ImportableAttribute
 *
 * @property Importable   $importable All attributes must have a parent importable.
 *                                  For example a post meta attribute has an importable post object.
 * @property ErrorHandler $error_handler All attributes must have an error handler
 *                                       passed from the parent importable.
 * @package Geniem\Oopi\Interfaces
 */
interface Attribute {

    /**
     * Setter for the parent object.
     *
     * @param Importable $importable The parent importable.
     *
     * @return self Return self for operation chaining.
     */
    public function set_importable( Importable $importable ) : self;

    /**
     * Saves the attribute for the importable.
     *
     * @return int|string It is encouraged that the id of the saved attribute is returned.
     */
    public function save();
}
