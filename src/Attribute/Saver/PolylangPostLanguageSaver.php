<?php
/**
 * Handles saving Polylang localisation data for posts.
 */

namespace Geniem\Oopi\Attribute\Saver;

use Geniem\Oopi\Attribute\Language;
use Geniem\Oopi\Exception\AttributeSaveException;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Storage;

/**
 * Class PolylangPostLanguageSaver
 *
 * @package Geniem\Oopi\Attribute\Saver
 */
class PolylangPostLanguageSaver implements AttributeSaver {

    /**
     * Saves the Polylang localisation data for a post importable.
     *
     * @param Importable $importable A save operation is always related to an importable.
     * @param Attribute  $attribute  A save operation is always related to an attribute.
     *
     * @throws AttributeSaveException An error should be thrown for erroneous saves.
     *
     * @return int|string|void
     */
    public function save( Importable $importable, Attribute $attribute ) {
        if ( ! $importable instanceof PostImportable ) {
            throw new AttributeSaveException(
                'Unable to save post localization for an object of type: ' . get_class( $importable )
            );
        }
        if ( ! $attribute instanceof Language ) {
            throw new AttributeSaveException(
                'Unable to save post localization for an attribute of type: ' . get_class( $attribute )
            );
        }

        // Get needed variables
        $post_id = $importable->get_post_id();
        $wp_post = get_post( $post_id );
        $locale  = $attribute->get_locale();
        $main_id = $attribute->get_main_oopi_id();

        if ( ! $this->language_exists( $locale ) ) {
            throw new AttributeSaveException(
                "Unable to save post localization for an unknown locale: $locale.
                Make sure the language is installed before importing objects."
            );
        }

        // Set post locale.
        \pll_set_post_language( $post_id, $locale );

        // If a post name was set in the data and it doesn't match the database,
        // update post name to allow PLL to handle unique slugs.
        if (
            $importable->get_post_name() &&
            $importable->get_post_name() !== $wp_post->post_name
        ) {
            wp_update_post(
                [
                    'ID'        => $importable->get_post_id(),
                    'post_name' => $importable->get_post_name(),
                ]
            );
        }

        // Run only if a main object exists
        if ( $main_id ) {
            // Get master post id for translation linking
            $main_post_id = Storage::get_post_id_by_oopi_id( $main_id );
            $main_locale  = \pll_get_post_language( $main_post_id );

            // Set the link for translations if a matching post was found.
            if ( $main_post_id ) {

                // Get current translation.
                $current_translations = \pll_get_post_translations( $main_post_id );

                // Set up new translations.
                $new_translations = [
                    $main_locale => $main_post_id,
                    $locale      => $post_id,
                ];

                $parsed_args = \wp_parse_args( $new_translations, $current_translations );

                // Link translations.
                \pll_save_post_translations( $parsed_args );
            }
        }
    }

    /**
     * Check if a language is installed.
     *
     * @param string $locale The PLL locale code.
     *
     * @return bool
     */
    protected function language_exists( string $locale ) : bool {
        return in_array( $locale, pll_languages_list(), true );
    }

}
