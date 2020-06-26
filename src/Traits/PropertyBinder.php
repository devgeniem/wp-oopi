<?php
/**
 * Contains a helper method for binding data into objects.
 */

namespace Geniem\Oopi\Traits;

/**
 * Trait PropertyBinder
 */
trait PropertyBinder {

    /**
     * A helper method for adding all data with a single call.
     *
     * @param array|object $data The term data.
     * @return self
     */
    public function set_data( $data ) : self {
        foreach ( (array) $data as $key => $value ) {
            if ( $key === 'oopi_id' ) {
                // Not allowed after instantiation.
                continue;
            }

            // Validate if a validation method exists.
            $valid = method_exists( $this, "validate_$key" ) ?
                call_user_func( [ $this, "validate_$key" ] ) : true;

            if ( property_exists( $this, $key ) && $valid ) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

}
