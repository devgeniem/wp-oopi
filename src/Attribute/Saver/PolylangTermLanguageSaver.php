<?php
/**
 * Handles saving Polylang localisation data for terms.
 */

namespace Geniem\Oopi\Attribute\Saver;

use Geniem\Oopi\Attribute\Language;
use Geniem\Oopi\Importable\TermImportable;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Localization\PolylangUtil;
use Geniem\Oopi\Storage;

/**
 * Class PolylangTermLanguageSaver
 *
 * @package Geniem\Oopi\Attribute\Saver
 */
class PolylangTermLanguageSaver implements AttributeSaver {

    /**
     * Saves the Polylang localisation data for a term importable.
     *
     * @param Importable $importable A save operation is always related to an importable.
     * @param Attribute  $attribute  A save operation is always related to an attribute.
     *
     * @return int|string|void
     */
    public function save( Importable $importable, Attribute $attribute ) {
        if ( ! $importable instanceof TermImportable ) {
            $importable->get_error_handler()->set_error(
                'Unable to save term localization for an object of type: ' . get_class( $importable )
            );
            return;
        }
        if ( ! $attribute instanceof Language ) {
            $importable->get_error_handler()->set_error(
                'Unable to save term localization for an attribute of type: ' . get_class( $attribute )
            );
            return;
        }

        // Get needed variables.
        $term_id = $importable->get_wp_id();
        $wp_term = get_term( $term_id );
        $locale  = $attribute->get_locale();
        $main_id = $attribute->get_main_oopi_id();

        if ( ! PolylangUtil::language_exists( $locale ) ) {
            $importable->get_error_handler()->set_error(
                "Unable to save term localization for an unknown locale: $locale.
                Make sure the language is installed before importing objects."
            );
            return;
        }

        // Set term locale.
        \pll_set_term_language( $term_id, $locale );

        // If a term slug was set in the data and it doesn't match the database,
        // update term slug to allow PLL to handle unique slugs.
        // TODO: does this really work?
        if (
            $importable->get_term_slug() &&
            $importable->get_term_slug() !== $wp_term->slug
        ) {
            wp_update_term(
                $term_id,
                $wp_term->taxonomy,
                [
                    'slug' => $importable->get_term_slug(),
                ]
            );
        }

        // Run only if a main object exists.
        if ( $main_id ) {
            // Get main term id for translation linking
            $main_term_id = Storage::get_term_id_by_oopi_id( $main_id );
            $main_locale  = \pll_get_term_language( $main_term_id );

            // Set the link for translations if a matching term was found.
            if ( $main_term_id ) {

                // Get current translation.
                $current_translations = \pll_get_term_translations( $main_term_id );

                // Set up new translations.
                $new_translations = [
                    $main_locale => $main_term_id,
                    $locale      => $term_id,
                ];

                $parsed_args = \wp_parse_args( $new_translations, $current_translations );

                // Link translations.
                \pll_save_term_translations( $parsed_args );
            }
        }
    }
}
