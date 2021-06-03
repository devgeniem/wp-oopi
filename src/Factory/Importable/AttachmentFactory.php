<?php
/**
 * A factory class for statically creating importable attachment objects.
 */

namespace Geniem\Oopi\Factory\Importable;

use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\AttachmentImportable;
use Geniem\Oopi\Interfaces\ImportableFactory;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Util;

/**
 * Class AttachmentImportableFactory
 *
 * @package Geniem\Oopi\Factory\Importable
 */
class AttachmentFactory implements ImportableFactory {

    /**
     * Creates a post importable object from the data.
     *
     * @param string $oopi_id A unique id for the importable.
     * @param mixed  $data    The importable data.
     *
     * @return AttachmentImportable
     */
    public static function create( string $oopi_id, $data ) : Importable {
        $importer      = Util::get_prop( $data, 'importer', null );
        $error_handler = Util::get_prop( $data, 'error_handler', null );

        $importable = new AttachmentImportable( $oopi_id, $importer, $error_handler );

        $src            = Util::get_prop( $data, 'src', null );
        $title          = Util::get_prop( $data, 'title', null );
        $alt            = Util::get_prop( $data, 'alt', null );
        $caption        = Util::get_prop( $data, 'caption', null );
        $description    = Util::get_prop( $data, 'description', null );
        $parent_wp_id   = Util::get_prop( $data, 'parent_wp_id', null );
        $parent_oopi_id = Util::get_prop( $data, 'parent_oopi_id', null );
        $is_thumbnail   = Util::get_prop( $data, 'is_thumbnail', false );
        $meta           = Util::get_prop( $data, 'meta', null );
        $language       = Util::get_prop( $data, 'language', null );

        if ( $title ) {
            $importable->set_title( $title );
        }

        if ( $src ) {
            $importable->set_src( $src );
        }

        if ( $alt ) {
            $importable->set_alt( $alt );
        }

        if ( $caption ) {
            $importable->set_caption( $caption );
        }

        if ( $description ) {
            $importable->set_description( $description );
        }

        if ( $parent_wp_id ) {
            $importable->set_parent_wp_id( $parent_wp_id );
        }

        if ( $parent_oopi_id ) {
            $importable->set_parent_oopi_id( $parent_oopi_id );
        }

        // Defaults to false.
        $importable->set_is_thumbnail( $is_thumbnail );

        if ( is_array( $meta ) ) {
            try {
                $importable->set_meta( $meta );
            }
            catch ( TypeException $e ) {
                $importable->get_error_handler()->set_error( $e->getMessage(), $e );
            }
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
