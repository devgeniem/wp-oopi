<?php
/**
 * The base class for meta attributes.
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Exception\AttributeSaveException;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Util;

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
     * @return string
     */
    public function get_value(): string {
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
     * @return int|string|void|null
     * @throws AttributeSaveException An error is thrown if the saving fails.
     */
    public function save() {
        if ( ! empty( $this->saver ) ) {
            return $this->saver->save( $this->importable, $this );
        }

        return null;
    }

    /**
     * Factory method for creating the object from raw data.
     *
     * @param Importable   $importable The importable object.
     * @param array|object $data       The field data.
     *
     * @return Meta
     */
    public static function factory( Importable $importable, $data ) : Meta {
        $key   = Util::get_prop( $data, 'key' );
        $value = Util::get_prop( $data, 'value' );
        $saver = Util::get_prop( $data, 'saver', null );

        return new static( $importable, $key, $value, $saver );
    }
}
