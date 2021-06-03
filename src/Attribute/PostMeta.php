<?php
/**
 * Attribute
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Attribute\Saver\PostMetaSaver;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\AttributeSaver;

/**
 * Class PostMeta
 *
 * @package Geniem\Oopi\Attribute
 */
class PostMeta extends Meta {

    /**
     * PostMeta constructor.
     *
     * @param Importable          $importable The importable.
     * @param string              $key        The meta key.
     * @param mixed               $value      The meta value.
     * @param AttributeSaver|null $saver      An optional saver. The PostMetaSaver by default.
     *
     * @throws TypeException Throws an error if the importable is not the correct type.
     */
    public function __construct(
        Importable $importable,
        string $key,
        $value = null,
        ?AttributeSaver $saver = null
    ) {
        if ( ! $importable instanceof PostImportable ) {
            throw new TypeException( 'PostMeta requires an importable of type: ' . PostImportable::class );
        }

        // Set default handlers if not passed.
        $saver = $saver ?? new PostMetaSaver();

        parent::__construct( $importable, $key, $value, $saver );
    }
}
