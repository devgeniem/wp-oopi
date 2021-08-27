<?php
/**
 * A factory class for statically creating importable post objects.
 */

namespace Geniem\Oopi\Factory\Importable;

use Geniem\Oopi\Exception\PostException;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\ImportableFactory;
use Geniem\Oopi\Util;

/**
 * Class PostFactory
 *
 * @package Geniem\Oopi\Factory
 */
class PostFactory implements ImportableFactory {

    /**
     * Creates a post importable object from the data.
     *
     * @param string $oopi_id A unique id for the importable.
     * @param mixed  $data    The importable data.
     *
     * @return PostImportable
     * @throws PostException Thrown if there is no post set in the data.
     */
    public static function create( string $oopi_id, $data ) : Importable {
        $importer      = Util::get_prop( $data, 'importer', null );
        $error_handler = Util::get_prop( $data, 'error_handler', null );

        $importable = new PostImportable( $oopi_id, $importer, $error_handler );

        $post        = Util::get_prop( $data, 'post', null );
        $attachments = Util::get_prop( $data, 'attachments', null );
        $meta        = Util::get_prop( $data, 'meta', null );
        $terms       = Util::get_prop( $data, 'terms', null );
        $acf         = Util::get_prop( $data, 'acf', null );
        $language    = Util::get_prop( $data, 'language', null );

        if ( empty( $post ) ) {
            throw new PostException( 'The required "post" property not found in data passed for: ' . __CLASS__ );
        }

        // Post
        $importable->set_post( $post );

        // Attachments
        if ( is_array( $attachments ) ) {
            $importable->set_attachments( $attachments );
        }

        // Post meta
        if ( is_array( $meta ) ) {
            try {
                $importable->set_meta( $meta );
            }
            catch ( TypeException $e ) {
                $importable->get_error_handler()->set_error( $e->getMessage(), $e );
            }
        }

        // Terms
        if ( is_array( $terms ) ) {
            $importable->set_terms( $terms );
        }

        // Advanced Custom Fields
        if ( is_array( $acf ) ) {
            $importable->set_acf( $acf );
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
