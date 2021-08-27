<?php
/**
 * A factory class for statically creating term importable objects.
 */

namespace Geniem\Oopi\Factory\Importable;

use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\TermImportable;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\ImportableFactory;
use Geniem\Oopi\Util;

/**
 * Class TermFactory
 *
 * @package Geniem\Oopi\Factory
 */
class TermFactory implements ImportableFactory {

    /**
     * Creates a post importable object from the data.
     *
     * @param string $oopi_id A unique id for the importable.
     * @param mixed  $data    The importable data.
     *
     * @return TermImportable
     */
    public static function create( string $oopi_id, $data ) : Importable {
        $importer      = Util::get_prop( $data, 'importer', null );
        $error_handler = Util::get_prop( $data, 'error_handler', null );

        $importable = new TermImportable( $oopi_id, $importer, $error_handler );

        $term        = Util::get_prop( $data, 'term', null );
        $name        = Util::get_prop( $data, 'name', null );
        $slug        = Util::get_prop( $data, 'slug', null );
        $description = Util::get_prop( $data, 'description', null );
        $taxonomy    = Util::get_prop( $data, 'taxonomy', null );
        $parent      = Util::get_prop( $data, 'parent', null );
        $meta        = Util::get_prop( $data, 'meta', null );
        $language    = Util::get_prop( $data, 'language', null );

        if ( $term ) {
            $importable->set_term( $term );
        }

        if ( $name ) {
            $importable->set_name( $name );
        }

        if ( $slug ) {
            $importable->set_slug( $slug );
        }

        if ( $description ) {
            $importable->set_description( $description );
        }

        if ( $taxonomy ) {
            $importable->set_taxonomy( $taxonomy );
        }

        if ( $parent ) {
            $importable->set_parent( $parent );
        }

        // Term meta
        if ( is_array( $meta ) ) {
            $importable->set_meta( $meta );
        }

        // Language
        if ( ! empty( $language ) ) {
            try {
                $importable->set_language( $language );
            }
            catch ( TypeException $e ) {
                $importable->get_error_handler()->set_error( $e->getMessage(), $e );
            }
        }

        return $importable;
    }
}
