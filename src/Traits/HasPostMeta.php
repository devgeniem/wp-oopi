<?php
/**
 * Used in importables with post meta.
 */

namespace Geniem\Oopi\Traits;

use Geniem\Oopi\Attribute\PostMeta;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Factory\Attribute\PostMetaFactory;
use Geniem\Oopi\Interfaces\Importable;

/**
 * Trait HasPostMeta
 *
 * @package Geniem\Oopi\Traits
 */
trait HasPostMeta {

    /**
     * Post meta.
     *
     * @var PostMeta[]
     */
    protected array $meta = [];

    /**
     * Sets the post meta data.
     *
     * @param array $meta_data The meta data.
     *
     * @throws TypeException Thrown if the setter is not used in an importable.
     */
    public function set_meta( array $meta_data = [] ) {
        // Cast data to PostMeta.
        $this->meta = array_filter( array_map( function( $meta ) {
            if ( $meta instanceof PostMeta ) {
                return $meta;
            }

            if ( ! $this instanceof Importable ) {
                throw new TypeException( 'Only an importable can have post meta.' );
            }

            return PostMetaFactory::create( $this, $meta );
        }, $meta_data ) );
    }

    /**
     * Getter for post meta.
     *
     * @return PostMeta[]
     */
    public function get_meta() : array {
        return $this->meta;
    }
}
