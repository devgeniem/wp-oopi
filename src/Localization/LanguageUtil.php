<?php
/**
 * Language utility functions.
 */

namespace Geniem\Oopi\Localization;

use Geniem\Oopi\Attribute\Saver\PolylangPostLanguageSaver;
use Geniem\Oopi\Attribute\Saver\PolylangTermLanguageSaver;
use Geniem\Oopi\Importable\AttachmentImportable;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Importable\TermImportable;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class LanguageUtil
 *
 * @package Geniem\Oopi
 */
class LanguageUtil {

    /**
     * Constant for identifying the Polylang plugin.
     */
    const POLYLANG_KEY = 'polylang';

    /**
     * Constant for identifying the WPML plugin.
     */
    const WPML_KEY = 'wpml';

    /**
     * Initialize any of the supported localization plugin handler if one is installed.
     */
    public static function init() {
        PolylangUtil::init();
    }

    /**
     * Saves WP term language data with your installed WordPress translation plugin.
     * The actual language saving is done in corresponding plugin classes.
     *
     * @param TermImportable $term The Oopi term.
     * @param PostImportable $post The Oopi post.
     *
     * @return boolean
     */
    public static function set_term_language( TermImportable $term, PostImportable $post ) {

        // Check which translation plugin should be used
        $activated_i18n_plugin = self::get_activated_plugin();

        // If no translation plugin was detected.
        if ( $activated_i18n_plugin === false ) {
            return false;
        }

        // If Polylang is activated use Polylang.
        if ( $activated_i18n_plugin === 'polylang' ) {

            PolylangUtil::set_term_language( $term, $post );

            return true;
        }

        return false;
    }

    /**
     * Checks which translation plugin to use.
     * On success returns slug of supported WordPress translation plugins. 'wpml', 'polylang'
     * if translation plugin is not found returns false.
     *
     * @return string|null The key for active plugin. Null if non found.
     */
    public static function get_activated_plugin() : ?string {
        $active_plugin = [
            function_exists( 'PLL' )    => static::POLYLANG_KEY, // Polylang is active.
            class_exists( 'SitePress' ) => static::WPML_KEY, // WPML is active.
        ];

        // Get the first plugin key that evaluates to true.
        return $active_plugin[ true ] ?? null;
    }

    /**
     * Get the default language attribute saver for a given importable.
     *
     * @param Importable $importable    The importable to use the language.
     * @param string     $active_plugin The active translations plugin key.
     *
     * @return AttributeSaver|null
     */
    public static function get_default_language_saver(
        Importable $importable,
        string $active_plugin
    ) : ?AttributeSaver {
        switch ( get_class( $importable ) ) {
            case PostImportable::class:
            case AttachmentImportable::class:
                return self::post_language_saver_factory( $active_plugin );
            case TermImportable::class:
                return self::term_language_saver_factory( $active_plugin );
            default:
                return null;
        }
    }

    /**
     * A factory method for creating a plugin specific post language saver.
     *
     * @param string $plugin The active translations plugin key.
     *
     * @return AttributeSaver|null
     */
    public static function post_language_saver_factory( string $plugin ) : ?AttributeSaver {
        switch ( $plugin ) {
            case self::POLYLANG_KEY:
                return new PolylangPostLanguageSaver();
            default:
                return null;
        }
    }

    /**
     * A factory method for creating a plugin specific term language saver.
     *
     * @param string $plugin The active translations plugin key.
     *
     * @return AttributeSaver|null
     */
    public static function term_language_saver_factory( string $plugin ) : ?AttributeSaver {
        switch ( $plugin ) {
            case self::POLYLANG_KEY:
                return new PolylangTermLanguageSaver();
            default:
                return null;
        }
    }

}
