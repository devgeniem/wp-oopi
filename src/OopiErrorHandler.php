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
     * Error messages and data.
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * The error scope.
     *
     * @var string
     */
    private string $scope;

    /**
     * OopiErrorHandler constructor.
     *
     * @param string $scope The error scope.
     */
    public function __construct( string $scope ) {
        $this->scope = $scope;
    }

    /**
     * Set a single error and store it in the class.
     *
     * @param string     $error The error message.
     * @param mixed|null $data  Optional data related to the error.
     */
    public function set_error( $error = '', $data = null ) {
        $this->errors[ $this->scope ] = $this->errors[ $this->scope ] ?? [];

        $this->errors[ $this->scope ][] = [
            'message' => $error,
            'data'    => $data,
        ];

        // Maybe log errors.
        if ( Settings::get( 'log_errors' ) ) {
            $scope = $this->scope;
            error_log( "OOPI ERROR - $scope - $error" ); // phpcs:ignore
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
