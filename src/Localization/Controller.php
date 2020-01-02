<?php
/**
 * Plugin localization controller.
 */

namespace Geniem\Oopi\Localization;

// Classes
use Geniem\Oopi\Post as Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Localization
 *
 * @package Geniem\Oopi
 */
class Controller {

    /**
     * Initialize any of the supported localization plugin handler if one is installed.
     */
    public static function init() {
        Polylang::init();
    }

    /**
     * Saves plugin with your installed WordPress translation plugin.
     * The actual locale saving is happening in Plugin specific classes
     * Geniem\Oopi\Localization\Polylang and Geniem\Oopi\Localization\WPML
     *
     * @param Post $post Instance of the Post class.
     *
     * @return boolean
     */
    public static function save_locale( &$post ) {

        // Check which translation plugin should be used
        $activated_i18n_plugin = self::get_activated_i18n_plugin( $post );

        // If no translation plugin was detected.
        if ( $activated_i18n_plugin === false ) {
            return false;
        }

        // If Polylang is activated use Polylang.
        if ( $activated_i18n_plugin === 'polylang' ) {
            Polylang::save_pll_locale( $post );
        }
    }

    /**
     * Checks which translation plugin to use.
     * On success returns slug of supported WordPress translation plugins. 'wpml', 'polylang'
     * if translation plugin is not found returns false.
     *
     * @param Post $post The current importer object.
     *
     * @return string|boolean
     */
    public static function get_activated_i18n_plugin( &$post ) {

        // Checks if Polylang is installed and activated
        $polylang_activated = function_exists( 'PLL' );

        // If Polylang is activated use Polylang
        if ( $polylang_activated === true ) {
            return 'polylang';
        }

        // If Polylang or wpml is not active leave an error message for debugging
        if ( $polylang_activated === false ) {
            // Show an error if translation engines aren't activated and user is willing to translate
            $err = __( 'Error, translation plugin does not seem to be activated. Please install and activate your desired translation plugin to start translations.', 'geniem-importer' );
            $post->set_error( 'i18n', '', $err );
            return false;
        }
    }

}
