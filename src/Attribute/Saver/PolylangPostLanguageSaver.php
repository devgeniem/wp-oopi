<?php
/**
 * Handles saving Polylang localisation data for posts.
 */

namespace Geniem\Oopi\Attribute\Saver;

use Geniem\Oopi\Attribute\Language;
use Geniem\Oopi\Importable\AttachmentImportable;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Localization\PolylangUtil;
use Geniem\Oopi\Storage;

/**
 * Class PolylangPostLanguageSaver
 *
 * @package Geniem\Oopi\Attribute\Saver
 */
class PolylangPostLanguageSaver implements AttributeSaver {

    /**
     * This saver can handle these importable types.
     *
     * @var string[]
     */
    protected $allowed_types = [
        PostImportable::class,
        AttachmentImportable::class,
    ];

    /**
     * Saves the Polylang localisation data for a post importable.
     *
     * @param Importable $importable A save operation is always related to an importable.
     * @param Attribute  $attribute  A save operation is always related to an attribute.
     *
     * @return int|string|void
     */
    public function save( Importable $importable, Attribute $attribute ) {
        if ( ! in_array( get_class( $importable ), $this->allowed_types, true ) ) {
            $importable->get_error_handler()->set_error(
                'Unable to save post localization for an object of type: ' . get_class( $importable )
            );
            return;
        }
        if ( ! $attribute instanceof Language ) {
            $importable->get_error_handler()->set_error(
                'Unable to save post localization for an attribute of type: ' . get_class( $attribute )
            );
            return;
        }

        // Get needed variables.
        $post_id = $importable->get_wp_id();
        $wp_post = get_post( $post_id );
        $locale  = $attribute->get_locale();
        $main_id = $attribute->get_main_oopi_id();

        if ( ! PolylangUtil::language_exists( $locale ) ) {
            $importable->get_error_handler()->set_error(
                "Unable to save post localization for an unknown locale: $locale.
                Make sure the language is installed before importing objects."
            );
            return;
        }

        // Set post locale.
        \pll_set_post_language( $post_id, $locale );

        // If a post name was set in the data and it doesn't match the database,
        // update post name to allow PLL to handle unique slugs.
        // TODO: does this really work?
        if (
            $importable->get_post_name() &&
            $importable->get_post_name() !== $wp_post->post_name
        ) {
            wp_update_post(
                [
                    'ID'        => $importable->get_wp_id(),
                    'post_name' => $importable->get_post_name(),
                ]
            );
        }

        // Run only if a main object exists
        if ( $main_id ) {
            // Get main post id for translation linking
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

}
