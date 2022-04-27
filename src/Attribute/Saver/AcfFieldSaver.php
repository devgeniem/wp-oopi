<?php
/**
 * The attribute saver for ACF fields.
 */

namespace Geniem\Oopi\Attribute\Saver;

use Geniem\Oopi\Attribute\AcfField;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Factory\Importable\TermFactory;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Importable\TermImportable;
use Geniem\Oopi\Importable\AttachmentImportable;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Storage;
use Geniem\Oopi\Util;

/**
 * Class AcfFieldSaver
 *
 * @package Geniem\Oopi\Attribute\Saver
 */
class AcfFieldSaver implements AttributeSaver {

    /**
     * Updates the ACF field data for the given importable object.
     *
     * @param Importable $importable A save operation is always related to an importable.
     * @param Attribute  $attribute  A save operation is always related to an attribute.
     *
     * @return mixed|null Boolean containing the field update return value, null otherwise.
     * @throws TypeException
     */
    public function save( Importable $importable, Attribute $attribute ) {
        // Bail if ACF is not activated.
        if ( ! function_exists( 'get_field' ) ) {
            $importable->get_error_handler()->set_error(
                'Advanced Custom Fields is not active! Please install and activate the plugin to save ACF data.'
            );
            return null;
        }

        // Save a post field.
        if ( $importable instanceof PostImportable && $attribute instanceof AcfField ) {
            return $this->save_post_acf( $importable, $attribute );
        }
        if ( $importable instanceof TermImportable && $attribute instanceof AcfField ) {
            return $this->save_term_acf( $importable, $attribute );
        }
        if ( $importable instanceof AttachmentImportable && $attribute instanceof AcfField ) {
            return $this->save_post_acf( $importable, $attribute );
        }

        return null;
    }

    /**
     * Save field data for a post object.
     *
     * @param Importable $post  The post importable.
     * @param AcfField   $field The ACF field attribute.
     *
     * @return bool
     */
    protected function save_post_acf( Importable $post, AcfField $field ) {
        $value = $field->get_value();
        $key   = $field->get_key();

        // Filter the field before the update_field() method.
        $field = \apply_filters( 'oopi_before_save_post_acf', $field, $post );
        $field = \apply_filters( 'oopi_before_save_post_acf/type=' . $field->get_type(), $field, $post );

        switch ( $field->get_type() ) {
            case 'term':

                $terms = [];
                foreach ( $field->get_value() as $term ) {
                    if ( ! $term instanceof TermImportable ) {
                        $term = TermFactory::create( Util::get_prop( $term, 'oopi_id' ), $term );
                    }

                    // Try to get the WP id of the term importable.
                    $term_id = $term->get_wp_id() ?? Storage::get_term_id_by_oopi_id( $term->get_oopi_id() );

                    if ( $term_id ) {
                        $terms[] = $term_id;
                    }
                    else {
                        $post->get_error_handler()->set_error(
                            'Unable to set the ACF term field id. No WP id found for the given term importable.',
                            $term
                        );
                    }
                }
                if ( count( $terms ) ) {
                    return update_field( $key, $terms, $post->get_wp_id() );
                }
                break;

            case 'image':

                // Check if image exists.
                $attachment_post_id = Storage::get_post_id_by_oopi_id( $value );
                if ( ! empty( $attachment_post_id ) ) {
                    return update_field( $key, $attachment_post_id, $post->get_wp_id() );
                }
                else {
                    $post->get_error_handler()->set_error(
                        "Trying to set an image in an ACF field for a non-existent attachment.
                        Field key: $key. Attachment OOPI id: $value.",
                        [ $key => $value ]
                    );
                }
                break;

            default:
                return update_field( $key, $value, $post->get_wp_id() );
        }

        return false;
    }

    /**
     * Save field data for a term object.
     *
     * @param TermImportable $term  The term importable.
     * @param AcfField       $field The ACF field attribute.
     *
     * @throws TypeException Thrown if an error occurs.
     */
    protected function save_term_acf( TermImportable $term, AcfField $field ) {
        throw new TypeException( 'The ACF field saver does not contain an implementation for a term importable.' );
    }
}
