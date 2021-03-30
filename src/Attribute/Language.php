<?php
/**
 * The abstraction of all language attributes.
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Interfaces\ErrorHandlerInterface;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\ImportableAttribute;

/**
 * Class Language
 *
 * @package Geniem\Oopi\Attribute
 */
abstract class Language implements ImportableAttribute {

    /**
     * The id of the parent object in the main language.
     *
     * @var string
     */
    protected $main_oopi_id;

    /**
     * The locale.
     *
     * @var string
     */
    protected $locale;

    /**
     * Setter for the parent object.
     *
     * @param Importable $importable The parent importable.
     */
    public function set_importable( Importable $importable ) {
        $this->importable = $importable;
    }

    /**
     * @inheritDoc
     */
    public function save( ErrorHandlerInterface $error_handler ) {
        // TODO: Implement save() method.
    }

    /**
     * Get the locale.
     *
     * @return string
     */
    public function get_locale() : string {
        return $this->locale;
    }

    /**
     * Set the locale.
     *
     * @param string|null $locale The locale.
     *
     * @return Language Return self to enable chaining.
     */
    public function set_locale( ?string $locale ) : Language {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get the main object's OOPI id.
     *
     * @return string
     */
    public function get_main_oopi_id() : string {
        return $this->main_oopi_id;
    }

    /**
     * Set the main object id.
     *
     * @param string $main_oopi_id The main object's OOPI id.
     *
     * @return Language Return self to enable chaining.
     */
    public function set_main_oopi_id( ?string $main_oopi_id ) : Language {
        $this->main_oopi_id = $main_oopi_id;

        return $this;
    }

}
