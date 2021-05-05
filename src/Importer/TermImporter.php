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
use Geniem\Oopi\OopiErrorHandler;
use Geniem\Oopi\Storage;
use WP_Error;

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
     *
     * @param string $oopi_id The OOPI id.
     * @param int    $wp_id   The WP id.
     */
    public function identify( string $oopi_id, int $wp_id ) {
        if ( Storage::get_term_id_by_oopi_id( $oopi_id ) ) {
            // Do not reset.
            return;
        }

        // Set the queryable identificator.
        // Example: meta_key = 'oopi_id', meta_value = 12345
        add_term_meta( $wp_id, Storage::get_idenfiticator(), $oopi_id, true );

        $index_key = Storage::format_query_key( $oopi_id );

        // Set the indexed indentificator.
        // Example: meta_key = 'oopi_id_12345', meta_value = 12345
        add_term_meta( $wp_id, $index_key, $oopi_id, true );
    }

    /**
     * Import the term object.
     *
     * @param Importable        $importable    The object to be imported.
     * @param ErrorHandler|null $error_handler An optional error handler.
     *
     * @return int|null On success, the WP item id is returned, null on failure.
     * @throws TypeException Thrown if the importable is not a term importable.
     */
    public function import( Importable $importable, ?ErrorHandler $error_handler = null ) : ?int {
        do_action( 'oopi_before_term_import', $this );

        if ( ! $importable instanceof TermImportable ) {
            throw new TypeException( 'Term importer can only handle term importables.' );
        }

        $this->importable    = $importable;
        $this->error_handler = $error_handler ?? new OopiErrorHandler( TermImportable::ESCOPE );

        $wp_term = $this->importable->get_term();

        // If the term does not exist, create it.
        if ( ! $wp_term ) {
            $result = $this->insert_term( $this->importable );
            if ( is_wp_error( $result ) ) {
                $this->error_handler->set_error(
                    'An error occurred while creating the taxonomy term: ' . $this->importable->get_name(),
                    $result
                );
                return null;
            }
            $wp_term = get_term( $result['term_id'] );
            $this->importable->set_term( $wp_term );
        }

        // Identify the new term. Data is set only on firts run.
        $this->identify( $this->importable->get_oopi_id(), $wp_term->term_id );

        // Handle localization.
        if ( $this->importable->get_language() !== null ) {
            $this->save_language();
        }

        $this->importable->set_imported( true );

        // Hook for running functionalities after saving the post.
        do_action( 'oopi_after_term_import', $this );

        return $this->importable->get_wp_id();
    }

    /**
     * Create a new term.
     *
     * @param  TermImportable $term Term data.
     *
     * @return array|WP_Error An array containing the `term_id` and `term_taxonomy_id`,
     *                        WP_Error otherwise.
     */
    protected function insert_term( TermImportable $term ) {
        // If parent's Oopi id is set, fetch it. Default to 0.
        $parent    = $term->get_parent();
        $parent_id = $parent ? Storage::get_term_id_by_oopi_id( $parent ) : 0;

        // Insert the new term.
        $result = wp_insert_term(
            $term->get_name(),
            $term->get_taxonomy(),
            [
                'slug'        => $term->get_slug(),
                'description' => $term->get_description(),
                'parent'      => $parent_id,
            ]
        );

        return $result;
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
