<?php
/**
 * The attribute saver for ACF fields.
 */

namespace Geniem\Oopi\Attribute\Saver;

use Geniem\Oopi\Attribute\AcfField;
use Geniem\Oopi\Exception\AttributeSaveException;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Importable\TermImportable;
use Geniem\Oopi\Interfaces\Attribute;
use Geniem\Oopi\Interfaces\AttributeSaver;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Localization\LanguageUtil;
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
     * @throws AttributeSaveException An error should be thrown for erroneous saves.
     *
     * @return mixed|null Boolean containing the field update return value, null otherwise.
     */
    public function save( Importable $importable, Attribute $attribute ) {
        // Bail if ACF is not activated.
        if ( ! function_exists( 'get_field' ) ) {
            throw new AttributeSaveException(
                'Advanced Custom Fields is not active! Please install and activate the plugin to save ACF data.'
            );
        }

        // Save a post field.
        if ( $importable instanceof PostImportable && $attribute instanceof AcfField ) {
            return $this->save_post_acf( $importable, $attribute );
        }
        if ( $importable instanceof TermImportable && $attribute instanceof AcfField ) {
            return $this->save_term_acf( $importable, $attribute );
        }

        return null;
    }

    /**
     * Save field data for a post object.
     *
     * @param PostImportable $post  The post importable.
     * @param AcfField       $field The ACF field attribute.
     *
     * @throws AttributeSaveException Thrown if an error occurs.
     * @return bool
     */
    protected function save_post_acf( PostImportable $post, AcfField $field ) {
        $value = $field->get_value();
        $key   = $field->get_key();

        switch ( $field->get_type() ) {
            case 'taxonomy':
                $terms = [];
                foreach ( $field->get_value() as $term ) {
                    if ( ! $term instanceof TermImportable ) {
                        $term = ( new TermImportable( Util::get_prop( $term, 'oopi_id' ) ) )->set_data( $term );
                    }

                    // TODO: remove term creation from this method. Terms should be created before saving attributes.
                    // If the term does not exist, create it.
                    if ( ! $term->get_term() ) {
                        $result = Storage::create_new_term( $term, $post );
                        if ( is_wp_error( $result ) ) {
                            throw new AttributeSaveException(
                                "Unable to create the missing term for the ACF field: $key. Error: "
                                . $result->get_error_message()
                            );
                        }
                    }

                    // Ensure identification. Data is only set once.
                    $term->identify();

                    // Handle localization.
                    if ( $term->get_language() !== null ) {
                        LanguageUtil::set_term_language( $term, $post );
                    }

                    $terms[] = $term->get_wp_id();
                }
                if ( count( $terms ) ) {
                    return update_field( $key, $terms, $post->get_post_id() );
                }
                break;

            case 'image':
                // Check if image exists.
                $attachment_post_id = Storage::get_attachment_post_id_by_attachment_id( $value );
                if ( ! empty( $attachment_post_id ) ) {
                    return update_field( $key, $attachment_post_id, $post->get_post_id() );
                }
                else {
                    throw new AttributeSaveException(
                        "Trying to set an image in an ACF field that does not exists.
                        Field key: $key. Attachment OOPI id: $value."
                    );
                }

            default:
                // TODO: Test which field types require no extra logic.
                // Currently tested: 'select'
                return update_field( $key, $value, $post->get_post_id() );
        }

        return false;
    }

    /**
     * Save field data for a term object.
     *
     * @param TermImportable $term  The term importable.
     * @param AcfField       $field The ACF field attribute.
     *
     * @throws AttributeSaveException Thrown if an error occurs.
     */
    protected function save_term_acf( TermImportable $term, AcfField $field ) {
        // TODO: implement!
        throw new AttributeSaveException( 'Term field saving not implemented.' );
    }
}
