<?php

namespace Geniem\Oopi\Interfaces;

interface ErrorHandlerInterface {

    /**
     * Set an error.
     *
     * @param string $scope The error scope.
     * @param mixed  $data  The data related to the error.
     * @param string $error The error message.
     */
    public function set_error( $scope = '', $data = '', $error = '' );

    /**
     * Get all errors.
     *
     * @return array
     */
    public function get_errors() : array;

}