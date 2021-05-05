<?php
/**
 * Provides common language attribute functionalities for an importable.
 */

namespace Geniem\Oopi\Traits;

use Geniem\Oopi\Attribute\Language;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Util;

/**
 * Trait Translatable
 *
 * @package Geniem\Oopi\Traits
 */
trait Translatable {

    /**
     * The language data.
     *
     * @var Language
     */
    protected Language $language;

    /**
     * Get the language.
     *
     * @return Language
     */
    public function get_language() : Language {
        return $this->language;
    }

    /**
     * Sets the post's language data.
     *
     * @param Language|array|object $language The language data.
     *
     * @throws TypeException If the trait is not used in an importable, a type error is thrown on usage.
     */
    public function set_language( $language ) {
        if ( ! $this instanceof Importable ) {
            throw new TypeException( 'Only an importable can have a language.' );
        }

        try {
            if ( ! $language instanceof Language ) {
                $locale       = Util::get_prop( $language, 'locale' );
                $main_oopi_id = Util::get_prop( $language, 'main_oopi_id', null );
                $saver        = Util::get_prop( $language, 'saver', null );

                // Create the language object. Use the default saver if none is set.
                $this->language = new Language( $this, $locale, $main_oopi_id, $saver );
            }
            else {
                $this->language = $language;
            }
            $this->language->validate();
        }
        catch ( \Exception $e ) {
            $this->error_handler->set_error(
                'Unable to set language data for the post. Error: ' . $e->getMessage(), $e->getTrace()
            );
        }
    }

}