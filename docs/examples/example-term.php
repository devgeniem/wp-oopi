<?php
/**
 * An example of using the term factory to
 * create a term importable and importing it.
 */

use Geniem\Oopi\Exception\TermException;
use Geniem\Oopi\Exception\TypeException;
use Geniem\Oopi\Factory\Importable\TermFactory;

$importable = TermFactory::create( 'term_id_123', [
    'name'     => 'My term',
    'slug'     => 'my-term',
    'taxonomy' => 'category',
    'meta'     => [
        [
            'key'   => 'test_key1',
            'value' => 'Test value',
        ],
        [
            'key'   => 'test_key2',
            'value' => 2,
        ],
    ],
    'language' => [
        'locale' => 'en',
    ],
] );

try {
    $importable->import();
}
catch ( TermException $e ) {
    var_dump( $e ); // phpcs:ignore
}
catch ( TypeException $e ) {
    var_dump( $e ); // phpcs:ignore
}
