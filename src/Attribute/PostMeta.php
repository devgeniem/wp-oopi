<?php
/**
 * Attribute
 */

namespace Geniem\Oopi\Attribute;

use Geniem\Oopi\Attribute\Saver\PostMetaSaver;
use Geniem\Oopi\Interfaces\ErrorHandler;
use Geniem\Oopi\Importable\Post;
use Geniem\Oopi\Interfaces\Importable;
use Geniem\Oopi\Interfaces\AttributeSaver;

/**
 * Class PostMeta
 *
 * @package Geniem\Oopi\Attribute
 */
class PostMeta extends Meta {

    /**
     * The parent post object.
     *
     * @var Post
     */
    protected $importable;

    /**
     * PostMeta constructor.
     *
     * @param Importable          $importable    The importable.
     * @param string              $key           The meta key.
     * @param mixed               $value         The meta value.
     * @param AttributeSaver|null $saver         An optional saver. The PostMetaSaver by default.
     * @param ErrorHandler|null   $error_handler An optional error handler. Empty by default.
     */
    public function __construct(
        Importable $importable,
        string $key,
        $value = null,
        ?AttributeSaver $saver = null,
        ?ErrorHandler $error_handler = null
    ) {
        // Use the post meta saver by default.
        if ( empty( $saver ) ) {
            $saver = new PostMetaSaver();
        }

        parent::__construct( $importable, $key, $value, $saver, $error_handler );
    }

}
