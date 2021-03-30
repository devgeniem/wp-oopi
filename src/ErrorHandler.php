<?php
/**
 * The default error handler.
 */

namespace Geniem\Oopi;

use Geniem\Oopi\Interfaces\ErrorHandlerInterface;

/**
 * Class ErrorHandler
 *
 * @package Geniem\Oopi\Handler
 */
class ErrorHandler implements ErrorHandlerInterface {

    /**
     * @inheritDoc
     */
    public function set_error( $scope = '', $data = '', $error = '' ) {
        // TODO: Implement set_error() method.
    }

    /**
     * @inheritDoc
     */
    public function get_errors(): array {
        // TODO: Implement get_errors() method.
    }
}