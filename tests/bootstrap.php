<?php
/**
 * Bootsrtap PHPUnit tests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

function define_constanst() {
    if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
        define( 'HOUR_IN_SECONDS', 60 * 60 * 60 );
    }
}
define_constanst();

/**
 * Define mocks for WP functionalities.
 */
function wp_mocks() {
    WP_Mock::setUp();

    // Mock the plugin file data.
    WP_Mock::userFunction( 'get_file_data',
        [
            'return' => [
                'Version' => '1.0.0-beta',
            ]
        ]
    );

    // Mock the plugin dir url.
    WP_Mock::userFunction( 'plugin_dir_url', [] );

    // Mock the plugin activation hook.
    WP_Mock::userFunction( 'register_activation_hook',
        [
            // Nothig to do with the result.
            'return' => true,
        ]
    );

    // Mock an anonymous object to mock category as a registered taxonomy.
    $category = Mockery::mock();
    $category->shouldReceive( 'get_taxonomy' )
        ->andReturn( 'category' );
    $category->name = 'Test';
    $category->slug = 'test';
    $category->description = '';
    $category->taxonomy = 'category';

    WP_Mock::userFunction( 'get_taxonomies', [
        'return' => [
            $category
        ]
    ] );
}
wp_mocks();

require_once dirname( __DIR__ ) . '/plugin.php';
