<?php
/**
 * Polylang translations controller.
 */

namespace Geniem\Oopi\Localization;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Classes
use Geniem\Oopi\Language;
use Geniem\Oopi\Settings;
use Geniem\Oopi\Storage;
use Geniem\Oopi\Post;
use Geniem\Oopi\Term;
use Geniem\Oopi\Util;

/**
 * Class Polylang
 *
 * @package Geniem\Oopi
 */
class Polylang {
    /**
     * Holds Polylang.
     *
     * TODO: improve typing.
     *
     * @var object|null
     */
    protected static $polylang = null;

    /**
     * Holds polylang language codes.
     *
     * @var array
     */
    protected static array $languages = [];

    /**
     * Holds current attachment id.
     *
     * @var array
     */
    protected static array $current_attachment_ids = [];

    /**
     * Initialize.
     */
    public static function init() {
        $polylang = function_exists( 'PLL' ) ? PLL() : null;

        if ( ! empty( $polylang ) ) {
            /**
             * Get current languages.
             * Returns list of language codes.
             */
            self::$languages = pll_languages_list();

            // Media index might not be set by default.
            if ( isset( $polylang->options['media'] ) ) {

                // Check if media duplication is on.
                if ( $polylang->model->options['media_support'] && $polylang->options['media']['duplicate'] ?? 0 ) {
                    // Hook into media duplication so we can add attachment_id meta.
                    // add_action( 'pll_translate_media', array( __CLASS__, 'get_attachment_post_ids' ), 11, 3 );
                }
            }

            // Add a filter to prevent copying and synchronizing for Oopi identification data.
            add_filter( 'pll_copy_post_metas', [ __CLASS__, 'prevent_sync_and_copy_for_oopi_ids' ], 100, 2 );

            self::$polylang = $polylang;
        }
    }

    /**
     * Returns the polylang object.
     *
     * @return object|null Polylang object.
     */
    public static function pll() {
        return self::$polylang;
    }

    /**
     * Returns the polylang language list of language codes.
     *
     * @return array
     */
    public static function language_list() {
        return self::$languages;
    }

    /**
     * Get attachment by attachment id and language
     *
     * @param int    $attachment_post_id Attachment wp id.
     * @param string $language           The attachment locale.
     *
     * @return integer
     */
    public static function get_attachment_by_language( $attachment_post_id, $language ) {
        if ( isset( self::$polylang->filters_media ) ) {
            $attachment_translations = pll_get_post_translations( $attachment_post_id );
            $attachment_post_id      = $attachment_translations[ $language ] ?? $attachment_post_id;
        }
        return $attachment_post_id;
    }

    /**
     * Sets the term language.
     *
     * @param Term $term The Oopi term.
     * @param Post $post The Oopi post.
     *
     * @return void
     */
    public static function set_term_language( Term $term, Post $post ) {
        $wp_term = $term->get_term();

        if ( ! $wp_term instanceof \WP_Term ) {
            $post->set_error(
                'pll',
                $term,
                __( 'No WP term found in the Oopi term.', 'oopi' )
            );
        }

        // Bail if the taxonomy is not translated.
        if ( ! pll_is_translated_taxonomy( $wp_term->taxonomy ) ) {
            $post->set_error(
                'pll',
                $term,
                __( 'Unable to localize a term of an untranslated taxonomy.', 'oopi' )
            );
            return;
        }

        $language_obj = $term->get_language();
        $locale       = $language_obj->get_locale() ?? null;

        if ( empty( $locale ) ) {
            // Set the default language if no language was set.
            $locale = pll_default_language();
        }

        pll_set_term_language( $wp_term->term_id, $locale );

        // Try to set translations.
        if ( $language_obj instanceof Language && $language_obj->get_main_oopi_id() ) {
            $master = $language_obj->get_main_oopi_id();

            // Get master post id for translation linking
            $master_term_id = Storage::get_term_id_by_oopi_id( $master );
            $master_locale  = \pll_get_term_language( $master_term_id );

            // Set the link for translations if a matching post was found.
            if ( $master_term_id ) {

                // Get current translation.
                $current_translations = \pll_get_term_translations( $master_term_id );

                // Set up new translations.
                $new_translations = [
                    $master_locale => $master_term_id,
                    $locale        => $wp_term->term_id,
                ];

                $parsed_args = \wp_parse_args( $new_translations, $current_translations );

                // Link translations.
                \pll_save_term_translations( $parsed_args );
            }
        }
    }

    /**
     * Prevent PLL from copying or synchronizing Oopi's identification data.
     *
     * @param array $keys List of custom field names.
     *
     * @return array
     */
    public static function prevent_sync_and_copy_for_oopi_ids( $keys ) {
        $identificator = Storage::get_idenfiticator();

        // Remove Oopi indentificator meta keys.
        return array_filter( $keys, function( $key ) use ( $identificator ) {
            // Remove keys with the identificator.
            return strpos( $key, $identificator ) === false;
        } );
    }
}
