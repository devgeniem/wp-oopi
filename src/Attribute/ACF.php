<?php
/**
 * The attribute handler for importing ACF data for an importable.
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Interfaces\ErrorHandler;

/**
 * Class ACF
 *
 * @package Geniem\Oopi\Attribute
 */
class ACF extends Meta {

    /**
     * The ACF field type.
     *
     * @var string
     */
    protected $type;

    /**
     * Setter for the ACF field type (optional).
     * If not set, the value is saved as such.
     * This works for some, but not for all field types.
     *
     * @param string $type The ACF field type key.
     */
    public function set_type( string $type ) {
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function save( ErrorHandler $error_handler ) {
        // TODO: Implement save() method.
    }
}