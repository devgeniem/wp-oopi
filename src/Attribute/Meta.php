<?php
/**
 * The base class for meta attributes.
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\OopiErrorHandler;

/**
 * Class Meta
 *
 * @package Geniem\Oopi\Attribute
 */
abstract class Meta implements Attribute {

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
     * The attribute saver.
     *
     * @var AttributeSaver
     */
    protected $saver;

    /**
     * The error handler.
     *
     * @var ErrorHandler
     */
    protected $error_handler;

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
     * Set the key.
     *
     * @param string $key The key.
     *
     * @return Meta Return self to enable chaining.
     */
    public function set_key( string $key ): Meta {
        $this->key = $key;

        return $this;
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
     * Set the value.
     *
     * @param mixed $value The value.
     *
     * @return Meta Return self to enable chaining.
     */
    public function set_value( $value ): Meta {
        $this->value = $value;

        return $this;
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
     * @param ErrorHandler|null   $error_handler An optional error handler. OopiErrorHandler by default.
     */
    public function __construct(
        Importable $importable,
        string $key,
        $value = null,
        ?AttributeSaver $saver = null,
        ?ErrorHandler $error_handler = null
    ) {
        $this->importable    = $importable;
        $this->key           = $key;
        $this->value         = $value;
        $this->saver         = $saver;
        $this->error_handler = $error_handler ?: new OopiErrorHandler();
    }

    /**
     * Saves the attribute with the saver if it is set.
     *
     * @return int|string|void|null
     */
    public function save() {
        if ( ! empty( $save ) ) {
            return $this->saver->save( $this->importable, $this, $this->error_handler );
        }

        return null;
    }
}
