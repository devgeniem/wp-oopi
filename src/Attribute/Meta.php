<?php
/**
 * The base class for meta attributes.
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\ImportableAttribute;

/**
 * Class Meta
 *
 * @package Geniem\Oopi\Attribute
 */
abstract class Meta implements ImportableAttribute {

    /**
     * The importable object.
     *
     * @var Importable
     */
    protected $importable;

    /**
     * The meta key.
     *
     * @var string
     */
    protected $key;

    /**
     * The meta value.
     *
     * @var string
     */
    protected $value;

    /**
     * Setter for the parent object.
     *
     * @param Importable $importable The parent importable.
     */
    public function set_importable( Importable $importable ) {
        $this->importable = $importable;
    }

    /**
     * A meta attribute always has a key and a value.
     *
     * @param string $key   The meta key.
     * @param mixed  $value The meta value.
     */
    public function __construct( string $key, $value = null ) {
        $this->key   = $key;
        $this->value = $value;
    }
}
