<?php
/**
 * Plugin Name: Oopi
 * Plugin URI:  https://github.com/devgeniem/wp-oopi
 * Description: Oopi is an object-oriented developer friendly WordPress importer.
 * Version:     0.1.1
 * Author:      Geniem
 * Author URI:  http://www.github.com/devgeniem
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: oopi
 * Domain Path: /languages
 */

namespace Geniem\Oopi;

use Geniem\Oopi\Localization\Controller as LocalizationController;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// If a custom autoloader exists, use it.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * The base class for the plugin.
 *
 * @package Geniem
 */
class Plugin {

    /**
     * Holds the general plugin data.
     *
     * @var array
     */
    protected static $plugin_data = [
        'TABLE_NAME' => 'oopi_log',
    ];

    /**
     * Create required database tables on install.
     */
    public static function install() {
        // Install log handler.
        Log::install();
    }

    /**
     * Initialize the plugin.
     */
    public static function init() {
        // If a custom autoloader exists, use it.
        if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
            require_once __DIR__ . '/vendor/autoload.php';
        }

        // Set the plugin version.
        $plugin_data       = get_file_data( __FILE__, [ 'Version' => 'Version' ], 'plugin' );
        self::$plugin_data = wp_parse_args( $plugin_data, self::$plugin_data );

        // Set the basic settings.
        Settings::init( self::$plugin_data );

        // Initialize plugin controllers after plugins are loaded.
        add_action( 'wp_loaded', function() {
            LocalizationController::init();
        } );

        // Load the plugin textdomain.
        load_plugin_textdomain( 'oopi', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
}

// Initialize the plugin.
Plugin::init();

// Run installation on activation hook.
register_activation_hook( __FILE__, [ Plugin::class, 'install' ] );
