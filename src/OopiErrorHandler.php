<?php
/**
 * The default error handler.
 */

namespace Geniem\Oopi;

use Geniem\Oopi\Interfaces\ErrorHandler;

/**
 * Class ErrorHandler
 *
 * @package Geniem\Oopi\Handler
 */
class OopiErrorHandler implements ErrorHandler {

    /**
     * Error messages under correspondings scopes as the key.
     * Example:
     *      [
     *          'post' => [
     *              'post_title' => 'The post title is not valid.'
     *          ]
     *      ]
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Set a single error and store it in the class.
     *
     * @param string $scope The error scope.
     * @param mixed  $data  The data related to the error.
     * @param string $error The error message.
     */
    public function set_error( $scope = '', $data = '', $error = '' ) {
        // Get needed variables
        $oopi_id = $this->oopi_id;

        $this->errors[ $scope ] = $this->errors[ $scope ] ?? [];

        $message = '(' . Settings::get( 'id_prefix' ) . $oopi_id . ') ' . $error;

        $this->errors[ $scope ][] = [
            'message' => $message,
            'data'    => $data,
        ];

        // Maybe log errors.
        if ( Settings::get( 'log_errors' ) ) {
            // @codingStandardsIgnoreStart
            error_log( 'OOPI: ' . $error );
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * Get all errors.
     *
     * @return array
     */
    public function get_errors(): array {
        return $this->errors;
    }
}