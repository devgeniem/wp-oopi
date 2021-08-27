<?php
/**
 * This class extends the PHP Exception class with the ability
 * to store multiple errors when validating a post.
 */

namespace Geniem\Oopi\Exception;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TermException
 *
 * @package Geniem\Oopi\Exception
 */
class TermException extends \Exception {

    /**
     * Holds errors found in validating the post.
     *
     * @var array
     */
    protected $errors;

    /**
     * Get the errors.
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * TermException constructor.
     *
     * @param string $message The error message to be thrown thrown.
     * @param int    $code    An error code to be thrown.
     * @param array  $errors  An array of error messages.
     */
    public function __construct( $message = '', $code = 0, $errors = [] ) {

        // Set errors.
        $this->errors = $errors;

        // Construct the base.
        parent::__construct( $message, $code );
    }
}
