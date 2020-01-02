<?php
/**
 * The plugin utility class.
 */

namespace Geniem\Oopi;

/**
 * Class Util
 *
 * A collection of utility functions.
 *
 * @package Geniem\Oopi
 */
class Util {

    /**
     * Checks whether some data is a JSON string.
     *
     * @param mixed $data The data to be checked.
     *
     * @return bool
     */
    public static function is_json( $data ) {

        json_decode( $data );

        return ( json_last_error() === JSON_ERROR_NONE );
    }

    /**
     * A helper function for getting a property
     * from an object or an associative array.
     *
     * @param array  $item    An object or an associative array.
     * @param string $key     The item key we are trying to get.
     * @param string $default A default value to be returned if the item was not found.
     *
     * @return mixed
     */
    public static function get_prop( $item = [], $key = '', $default = '' ) {

        if ( is_array( $item ) && isset( $item[ $key ] ) ) {
            return $item[ $key ];
        }
        elseif ( is_object( $item ) && isset( $item->{$key} ) ) {
            return $item->{$key};
        }
        else {
            return $default;
        }
    }

    /**
     * A helper function for setting a property
     * into an object or an associative array.
     *
     * @param array|object $item  An object or an associative array as a reference.
     * @param string       $key   The property key we are trying to set.
     * @param mixed        $value The value for the property. Defaults to a null value.
     *
     * @return mixed
     */
    public static function set_prop( &$item = [], $key = '', $value = null ) {

        if ( is_array( $item ) ) {
            $item[ $key ] = $value;
        }
        elseif ( is_object( $item ) ) {
            $item->{$key} = $value;
        }

        return $value;
    }

    /**
     * Check if a string matches the post id query format.
     *
     * @param string $id_string The id string to inspect.
     *
     * @return string|false Returns the id without the prefix if valid, else returns false.
     */
    public static function is_query_id( $id_string ) {

        $oopi_id_prefix = Settings::get( 'id_prefix' );
        $prefix_length  = strlen( $oopi_id_prefix );
        if ( \strncmp( $id_string, $oopi_id_prefix, $prefix_length ) === 0 ) {
            return substr( $id_string, $prefix_length );
        }

        return false;
    }
}
