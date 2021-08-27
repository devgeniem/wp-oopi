<?php
/**
 * The abstraction of all language attributes.
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Exception\AttributeSaveException;
use Geniem\Oopi\Exception\LanguageException;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Localization\LanguageUtil;

/**
 * Class Language
 *
 * @package Geniem\Oopi\Attribute
 */
class Language implements Attribute {

    /**
     * The importable the attribute belongs to.
     *
     * @var Importable
     */
    protected Importable $importable;

    /**
     * The id of the parent object in the main language.
     *
     * @var string|null Should be null if this is in the default language.
     */
    protected ?string $main_oopi_id = null;

    /**
     * The locale.
     *
     * @var string
     */
    protected string $locale;

    /**
     * The attribute saver.
     *
     * @var AttributeSaver|null
     */
    protected ?AttributeSaver $saver;

    /**
     * Language constructor.
     *
     * @param Importable          $importable   The importable the attribute belongs to.
     * @param string              $locale       The locale code.
     * @param string|null         $main_oopi_id If this is not in the default language, a main object id should be set.
     * @param AttributeSaver|null $saver        An optional saver. If none is set, the default saver is used.
     */
    public function __construct(
        Importable $importable,
        string $locale,
        ?string $main_oopi_id = null,
        ?AttributeSaver $saver = null
    ) {
        $this->importable   = $importable;
        $this->locale       = $locale;
        $this->main_oopi_id = $main_oopi_id;
        $this->saver        = $saver ?? LanguageUtil::get_default_language_saver(
            $importable, LanguageUtil::get_activated_plugin()
        );
    }

    /**
     * Setter for the parent object.
     *
     * @param Importable $importable The parent importable.
     *
     * @return self Return self for operation chaining.
     */
    public function set_importable( Importable $importable ) : self {
        $this->importable = $importable;

        return $this;
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
     * @param string $locale The locale.
     *
     * @return Language Return self to enable chaining.
     */
    public function set_locale( string $locale ) : Language {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get the main object's OOPI id.
     *
     * @return string|null
     */
    public function get_main_oopi_id() : ?string {
        return $this->main_oopi_id;
    }

    /**
     * Set the main object id.
     *
     * @param string $main_oopi_id The main object's OOPI id.
     *
     * @return Language Return self to enable chaining.
     */
    public function set_main_oopi_id( string $main_oopi_id ) : Language {
        $this->main_oopi_id = $main_oopi_id;

        return $this;
    }

    /**
     * Validate the language data.
     */
    public function validate() {}

    /**
     * Saves the attribute with the saver if it is set.
     *
     * @return int|string|void|null
     * @throws LanguageException Thrown if saving fails.
     */
    public function save() {
        if ( ! empty( $this->saver ) ) {
            return $this->saver->save( $this->importable, $this );
        }

        throw new LanguageException( 'Unable to save the language. No saver defined for: ' . __CLASS__ );
    }
}
