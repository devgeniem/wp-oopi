<?php
/**
 * Plugin Name: Oopi
 * Plugin URI:  https://github.com/devgeniem/wp-oopi
 * Description: Oopi is an object-oriented developer friendly WordPress importer.
 * Version:     0.3.4
 * Author:      Geniem
 * Author URI:  http://www.github.com/devgeniem
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: oopi
 * Domain Path: /languages
 */

namespace Geniem\Oopi;

use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\Post;
use Geniem\Oopi\Importable\Term;
use Geniem\Oopi\Importer\PostImporter;
use Geniem\Oopi\Importer\TermImporter;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Localization\Controller as LocalizationController;
use ReflectionClass;
use ReflectionException;

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
     * A map of importable class names and their corresponding import handlers.
     *
     * @var Importable[]
     */
    private static $importables = [];

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

        // Initialize the import handlers for importables.
        self::set_initial_import_handlers();

        // Initialize plugin controllers after plugins are loaded.
        add_action( 'wp_loaded', function() {
            LocalizationController::init();
        } );

        // Load the plugin textdomain.
        load_plugin_textdomain( 'oopi', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Sets the initial state of global handlers for all importables.
     */
    protected static function set_initial_import_handlers() {
        static::$importables = [
            Post::class => PostImporter::class,
            Term::class => TermImporter::class,
        ];
    }

    /**
     * Add or override a global importable and/or its import handler.
     *
     * @param string $importable_class The importable class name.
     * @param string $importer_class   The importer class name.
     *
     * @throws ReflectionException|TypeException If any of the given classes are missing or they are of the wrong type,
     *                                           an error is thrown.
     */
    public static function set_importable( string $importable_class, string $importer_class ) {
        $importable_reflect = new ReflectionClass( $importable_class );
        $importer_reflect   = new ReflectionClass( $importer_class );

        $importable_interface = Importable::class;
        $importer_interface   = Importer::class;

        $valid_classes =
            $importable_reflect->implementsInterface( Importable::class )
            && $importer_reflect->implementsInterface( Importer::class );

        if ( $valid_classes ) {
            static::$importables[ $importable_class ] = $importer_class;
        }
        else {
            throw new TypeException(
                "The passed classes must implement the following interfaces: 
                importable: $importable_interface, importer: $importer_interface."
            );
        }
    }

    /**
     * Get the global importer for the given importable class name.
     *
     * @param string $importable_class The importable class name.
     *
     * @return Importer|null
     */
    public static function get_importer( string $importable_class ) : ?Importable {
        return static::$importables[ $importable_class ] ?: null;
    }
}

// Initialize the plugin.
Plugin::init();

// Run installation on activation hook.
register_activation_hook( __FILE__, [ Plugin::class, 'install' ] );
