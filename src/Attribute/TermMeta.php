<?php
/**
 * Term meta attribute.
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Attribute\Saver\TermMetaSaver;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\TermImportable;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\AttributeSaver;

/**
 * Class TermMeta
 *
 * @package Geniem\Oopi\Attribute
 */
class TermMeta extends Meta {

    /**
     * Error scope.
     */
    const ESCOPE = 'term_meta';

    /**
     * TermMeta constructor.
     *
     * @param Importable          $importable The importable.
     * @param string              $key        The meta key.
     * @param mixed               $value      The meta value.
     * @param AttributeSaver|null $saver      An optional saver. The TermMetaSaver by default.
     *
     * @throws TypeException Throws an error if the importable is not the correct type.
     */
    public function __construct(
        Importable $importable,
        string $key,
        $value = null,
        ?AttributeSaver $saver = null
    ) {
        if ( ! $importable instanceof TermImportable ) {
            throw new TypeException( 'TermMeta requires an importable of type: ' . TermImportable::class );
        }

        // Set default handlers if not passed.
        $saver = $saver ?? new TermMetaSaver();

        parent::__construct( $importable, $key, $value, $saver );
    }
}
