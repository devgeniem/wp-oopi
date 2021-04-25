<?php
/**
 * The default import handler for term objects.
 */

namespace Geniem\Oopi\Importer;

use Geniem\Oopi\Exception\LanguageException;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\TermImportable;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\Importer;
use Geniem\Oopi\Localization\LanguageUtil;
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Storage;
use Geniem\Oopi\Traits\ImporterAccessing;

/**
 * Class TermImporter
 *
 * @package Geniem\Oopi\Importer
 */
class TermImporter implements Importer {

    /**
     * Holds the importable object.
     *
     * @var TermImportable
     */
    protected TermImportable $importable;

    /**
     * The error handler.
     *
     * @var ErrorHandler
     */
    protected ErrorHandler $error_handler;

    /**
     * Get the importable.
     *
     * @return Importable
     */
    public function get_importable() : Importable {
        return $this->importable;
    }

    /**
     * Adds term meta rows for matching a WP term with an external source.
     */
    public function identify() {
        $oopi_id = $this->importable->get_oopi_id();

        // Set the queryable identificator.
        // Example: meta_key = 'oopi_id', meta_value = 12345
        add_term_meta( $this->importable->get_wp_id(), Storage::get_idenfiticator(), $oopi_id, true );

        $index_key = Storage::format_query_key( $oopi_id );

        // Set the indexed indentificator.
        // Example: meta_key = 'oopi_id_12345', meta_value = 12345
        add_term_meta( $this->importable->get_wp_id(), $index_key, $oopi_id, true );
    }

    /**
     * Import the term object.
     *
     * @param Importable        $importable    The object to be imported.
     * @param ErrorHandler|null $error_handler An optional error handler.
     *
     * @throws TypeException Thrown if the importable is not a term importable.
     *
     * @return int|null On success, the WP item id is returned, null on failure.
     */
    public function import( Importable $importable, ?ErrorHandler $error_handler = null ) : ?int {
        if ( ! $importable instanceof TermImportable ) {
            throw new TypeException( 'Term importer can only handle term importables.' );
        }

        $this->importable    = $importable;
        $this->error_handler = $error_handler ?? new OopiErrorHandler( TermImportable::ESCOPE );

        $wp_term = $this->importable->get_term();

        // If the term does not exist, create it.
        if ( ! $wp_term ) {
            $result = Storage::create_new_term( $this->importable );
            if ( is_wp_error( $result ) ) {
                $this->error_handler->set_error(
                    'An error occurred while creating the taxonomy term: ' . $this->importable->get_name(),
                    $result
                );
                return null;
            }
        }
        $wp_term = $this->importable->get_term();

        // Ensure identification. Data is only set once.
        $this->identify();

        // Handle localization.
        if ( $this->importable->get_language() !== null ) {
            $this->save_language();
        }

        $this->importable->set_term( $wp_term );

        $this->importable->is_imported();

        return $wp_term->term_id;
    }

    /**
     * Save language data.
     */
    protected function save_language() {
        try {
            $this->importable->get_language()->save();
        }
        catch ( LanguageException $e ) {
            $this->error_handler->set_error( $e->getMessage(), $e );
        }
    }
}
