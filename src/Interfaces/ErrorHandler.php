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
     * ErrorHandler constructor.
     *
     * @param string $scope The error scope, for example 'post'.
     */
    public function __construct( string $scope );

    /**
     * Set a single error and store it in the class.
     *
     * @param string     $error The error message.
     * @param mixed|null $data  Optional data related to the error.
     */
    public function set_error( string $error = '', $data = null );

    /**
     * Get all errors.
     *
     * @return array
     */
    public function get_errors() : array;

}
