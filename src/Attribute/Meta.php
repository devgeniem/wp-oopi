<?php
/**
 * The base class for meta attributes.
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;

/**
 * Class Meta
 *
 * @package Geniem\Oopi\Attribute
 */
abstract class Meta implements Attribute {

    /**
     * The meta key.
     *
     * @var string
     */
    protected string $key;

    /**
     * The meta value.
     *
     * @var mixed
     */
    protected $value;

    /**
     * The importable object.
     *
     * @var Importable
     */
    protected Importable $importable;

    /**
     * The attribute saver.
     *
     * @var AttributeSaver|null
     */
    protected ?AttributeSaver $saver;

    /**
     * Setter for the parent object.
     *
     * @param Importable $importable The parent importable.
     *
     * @return self Return self for operation chaining.
     */
    public function set_importable( Importable $importable ) : Attribute {
        $this->importable = $importable;

        return $this;
    }

    /**
     * Get the key.
     *
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * Get the value.
     *
     * @return mixed
     */
    public function get_value() {
        return $this->value;
    }

    /**
     * Meta constructor.
     * A meta attribute always has an importable it relates to, a key and a value.
     *
     * @param Importable          $importable    The importable.
     * @param string              $key           The meta key.
     * @param mixed               $value         The meta value.
     * @param AttributeSaver|null $saver         An optional saver. Empty by default.
     *                                           Pass a saver to make use of composition.
     */
    public function __construct(
        Importable $importable,
        string $key,
        $value = null,
        ?AttributeSaver $saver = null
    ) {
        $this->importable = $importable;
        $this->key        = $key;
        $this->value      = $value;
        $this->saver      = $saver;
    }

    /**
     * Saves the attribute with the saver if it is set.
     *
     * @return mixed|null
     */
    public function save() {
        if ( ! empty( $this->saver ) ) {
            return $this->saver->save( $this->importable, $this );
        }

        return null;
    }
}
