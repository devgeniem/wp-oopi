<?php

namespace Geniem\Oopi\Interfaces;

/**
 * Interface ErrorHandler
 *
 * @property array $errors Holds the errors.
 *
 * @package Geniem\Oopi\Interfaces
 */
interface ErrorHandler {

    /**
     * Set a single error and store it in the class.
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
