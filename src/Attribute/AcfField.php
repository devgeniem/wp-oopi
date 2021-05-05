<?php
/**
 * The attribute handler for importing ACF field data for an importable.
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Attribute\Saver\AcfFieldSaver;
use Geniem\Oopi\Exception\AttributeSaveException;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Util;

/**
 * Class AcfField
 *
 * @package Geniem\Oopi\Attribute
 */
class AcfField implements Attribute {

    /**
     * The field key.
     *
     * @var string
     */
    protected string $key;

    /**
     * The field value.
     *
     * @var mixed
     */
    protected $value;

    /**
     * The ACF field type.
     *
     * @var string
     */
    protected string $type;

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
     * Setter for the ACF field type (optional).
     * If not set, the value is saved as such.
     * This works for some, but not for all field types.
     *
     * @param string $type The ACF field type key.
     *
     * @return AcfField Returns self for chaining.
     */
    public function set_type( string $type ) : self {
        $this->type = $type;

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
     * Get the type.
     *
     * @return string
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Get the importable.
     *
     * @return Importable
     */
    public function get_importable(): Importable {
        return $this->importable;
    }

    /**
     * Get the saver.
     *
     * @return AttributeSaver|null
     */
    public function get_saver(): ?AttributeSaver {
        return $this->saver;
    }

    /**
     * AcfField constructor.
     *
     * @param Importable          $importable    The importable.
     * @param string              $key           The field key.
     * @param string              $type          The ACF field type key.
     * @param mixed               $value         The meta value.
     * @param AttributeSaver|null $saver         An optional saver. Empty by default.
     *                                           Pass a saver to make use of composition.
     */
    public function __construct(
        Importable $importable,
        string $key,
        string $type,
        $value = '',
        ?AttributeSaver $saver = null
    ) {
        $this->importable = $importable;
        $this->key        = $key;
        $this->type       = $type;
        $this->value      = $value;
        $this->saver      = $saver ?? new AcfFieldSaver();
    }

    /**
     * Save the field data with the saver.
     *
     * @return mixed|void Depends on the saver.
     */
    public function save() {
        return $this->saver->save( $this->importable, $this );
    }

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
}
