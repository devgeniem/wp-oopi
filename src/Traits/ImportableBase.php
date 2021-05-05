<?php
/**
 * Use this trait to introduce basic functionalities for an importable.
 */

namespace Geniem\Oopi\Traits;

use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Plugin;

/**
 * Trait ImportableBase
 *
 * @package Geniem\Oopi\Traits
 */
trait ImportableBase {

    /**
     * A unique id for external identification.
     *
     * @var string
     */
    protected string $oopi_id;

    /**
     * If this is an existing posts, the WP id is stored here.
     *
     * @var int|null
     */
    protected ?int $wp_id;

    /**
     * The error handler.
     *
     * @var ErrorHandler
     */
    protected ErrorHandler $error_handler;

    /**
     * Getter for OOPI id.
     *
     * @return string
     */
    public function get_oopi_id() : string {
        return $this->oopi_id;
    }

    /**
     * Getter for the WP id.
     *
     * @return int|null
     */
    public function get_wp_id() : ?int {
        return $this->wp_id;
    }

    /**
     * Set the WP id.
     *
     * @param int $wp_id The WP id.
     *
     * @return $this Return self to enable chaining.
     */
    public function set_wp_id( int $wp_id ) : self {
        $this->wp_id = $wp_id;

        return $this;
    }

    /**
     * Getter for the error handler.
     *
     * @return ErrorHandler
     */
    public function get_error_handler() : ErrorHandler {
        return $this->error_handler;
    }

    /**
     * Setter for the error handler.
     *
     * @param ErrorHandler $error_handler The error handler.
     *
     * @return $this Return self to enable chaining.
     */
    public function set_error_handler( ErrorHandler $error_handler ) : self {
        $this->error_handler = $error_handler;

        return $this;
    }

    /**
     * The basic validation checks whether there are any
     * errors stored in the error handler.
     *
     * @return bool
     */
    public function validate() : bool {
        return empty( $this->error_handler->get_errors() );
    }

    /**
     * Defines if the importable is already improted.
     *
     * @var bool
     */
    protected bool $imported = false;

    /**
     * Set the imported status.
     *
     * @param bool $imported The import status.
     *
     * @return $this Return self to enable chaining.
     */
    public function set_imported( bool $imported ) : self {
        $this->imported = $imported;

        return $this;
    }

    /**
     * Get the imported.
     *
     * @return bool
     */
    public function is_imported() : bool {
        return $this->imported;
    }

    /**
     * The importer instance.
     *
     * @var Importer
     */
    protected Importer $importer;

    /**
     * Setter for a local importer.
     *
     * @param Importer $importer The instance.
     *
     * @return $this Return self for chaining.
     */
    public function set_importer( Importer $importer ) : self {
        $this->importer = $importer;

        return $this;
    }

    /**
     * Get the object specific importer or the global one if local is not set.
     *
     * @return Importer The local or global importer.
     */
    public function get_importer() : Importer {
        return $this->importer ?: Plugin::get_importer( get_called_class() );
    }

    /**
     * Import the term using the attached importer.
     *
     * @return int|null The imported WP term id. Null if the import was not run.
     */
    public function import() : ?int {
        if ( ! empty( $this->importer ) && $this instanceof Importable ) {
            return $this->importer->import( $this, $this->error_handler );
        }

        return null;
    }
}
