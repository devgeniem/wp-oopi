<?php
/**
 * Plugin settings controller.
 */

namespace Geniem\Oopi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Settings
 *
 * @package Geniem\Oopi
 */
class Settings {

    /**
     * Holds the settings.
     *
     * @var array
     */
    protected static $settings = [];

    /**
     * Initializes the plugin settings.
     *
     * @param array $plugin_data The basic plugin settings.
     */
    public static function init( $plugin_data ) {
        // Sets the VERSION setting.
        self::$settings = $plugin_data;

        self::set( 'ID_PREFIX', 'oopi_id_' );
        self::set( 'LOG_ERRORS', false );
        self::set( 'TRANSIENT_KEY', 'oopi_' );
        self::set( 'TRANSIENT_EXPIRATION', HOUR_IN_SECONDS );
        self::set( 'TMP_FOLDER', '/tmp/' );
        self::set( 'LOG_STATUS_OK', 'OK' );
        self::set( 'LOG_STATUS_FAIL', 'FAIL' );
        self::set( 'CRON_INTERVAL_CLEAN_LOG', 'daily' );
    }

    /**
     * Get a single setting.
     *
     * @param string $key The setting key.
     *
     * @return mixed|null The setting value, if found, null if not.
     */
    public static function get( $key ) {
        $key = strtoupper( $key );

        // If a constant is set that matches the setting key, use it.
        $constant_key = 'OOPI_' . strtoupper( $key );
        if ( defined( $constant_key ) ) {
            return constant( $constant_key );
        }

        if ( isset( self::$settings[ $key ] ) ) {
            return self::$settings[ $key ];
        }
        else {
            return null;
        }
    }

    /**
     * Get all settings.
     *
     * @return array
     */
    public static function get_all() {
        return self::$settings;
    }

    /**
     * Setter for a single setting.
     * Every setting is overridable with constants.
     *
     * @param string $key   The setting key.
     * @param mixed  $value The setting value.
     */
    public static function set( $key, $value ) {
        $key = strtoupper( $key );
        if ( defined( $key ) ) {
            // Set the constant value.
            self::$settings[ $key ] = constant( $key );
        }
        else {
            // Set the value.
            self::$settings[ $key ] = $value;
        }
    }
}
