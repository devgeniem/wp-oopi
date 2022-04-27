<?php
/**
 * Bootstrap PHPUnit tests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
    define( 'HOUR_IN_SECONDS', 60 * 60 * 60 );
}

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
            // Nothing to do with the result.
            'return' => true,
        ]
    );

    // Mock the plugin deactivation hook.
    WP_Mock::userFunction( 'register_deactivation_hook',
        [
            // Nothing to do with the result.
            'return' => true,
        ]
    );

    // Mock an anonymous object to mock category as a registered taxonomy.
    $category = Mockery::mock();
    $category->shouldReceive( 'get_taxonomy' )
        ->set( 'name', 'Test' )
        ->set( 'slug', 'test' )
        ->set( 'description', '' )
        ->set( 'taxonomy', 'category' )
        ->andReturn( 'category' );

    WP_Mock::userFunction( 'get_taxonomies', [
        'return' => [
            $category
        ]
    ] );
}
wp_mocks();

require_once dirname( __DIR__ ) . '/plugin.php';
