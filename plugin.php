<?php
/**
 * Plugin Name: Oopi
 * Plugin URI:  https://github.com/devgeniem/wp-oopi
 * Description: Oopi is an object-oriented developer friendly WordPress importer.
 * Version:     1.4.0
 * Author:      Geniem
 * Author URI:  http://www.github.com/devgeniem
 * License:     GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Geniem\Oopi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// If a custom autoloader exists, use it.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Initialize the plugin.
Plugin::init();

// Run installation on activation hook.
\register_activation_hook( __FILE__, [ Plugin::class, 'install' ] );

// Run uninstallation on deactivation hook.
\register_deactivation_hook( __FILE__, [ Plugin::class, 'uninstall' ] );
