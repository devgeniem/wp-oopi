<?php

use Geniem\Oopi\Factory\Importable\PostFactory;
use Geniem\Oopi\Storage;

class TestPostFactory extends \PHPUnit\Framework\TestCase {

    public function test_create() {
        global $wpdb;

        $post_id = 'test_post';
        $oopi_post_key = Storage::format_query_key( $post_id );
        $term_id = 'test_term';
        $oopi_term_key = Storage::format_query_key( $term_id );
        $attachment_id = 'test_attachment';
        $oopi_attachment_key = Storage::format_query_key( $attachment_id );

        $wpdb = Mockery::mock( '\WPDB' );
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->termmeta = 'wp_termmeta';


        // Mock the get_wp_id_by_oopi_id() result.
        $wpdb->shouldReceive( 'prepare' )
            ->once()
            ->withArgs( [
                "SELECT DISTINCT post_id FROM wp_postmeta WHERE meta_key = %s",
                $oopi_post_key
            ])
            ->andReturn( "SELECT DISTINCT post_id FROM wp_postmeta WHERE meta_key = $oopi_post_key" );
        $wpdb->shouldReceive( 'get_col' )
             ->once()
             ->with( "SELECT DISTINCT post_id FROM wp_postmeta WHERE meta_key = $oopi_post_key" )
             ->andReturn( null ); // No post found.

        // Mock the get_wp_id_by_oopi_id() result for the attachment.
        $wpdb->shouldReceive( 'prepare' )
             ->once()
             ->withArgs( [
                 "SELECT DISTINCT post_id FROM wp_postmeta WHERE meta_key = %s",
                 $oopi_attachment_key
             ])
             ->andReturn( "SELECT DISTINCT post_id FROM wp_postmeta WHERE meta_key = $oopi_attachment_key" );
        $wpdb->shouldReceive( 'get_col' )
             ->once()
             ->with( "SELECT DISTINCT post_id FROM wp_postmeta WHERE meta_key = $oopi_attachment_key" )
             ->andReturn( null ); // No post found.

        // Mock the get_term_id_by_oopi_id() result.
        $wpdb->shouldReceive( 'prepare' )
             ->once()
             ->withArgs( [
                 "SELECT DISTINCT term_id FROM wp_termmeta WHERE meta_key = %s",
                 $oopi_term_key
             ])
             ->andReturn( "SELECT DISTINCT term_id FROM wp_termmeta WHERE meta_key = $oopi_term_key" );
        $wpdb->shouldReceive( 'get_col' )
             ->once()
             ->with( "SELECT DISTINCT term_id FROM wp_termmeta WHERE meta_key = $oopi_term_key" )
             ->andReturn( null ); // No post found.

        // Mock a post object.
        $post_mock = Mockery::mock( '\WP_Post' );
        $post_mock->ID = 0;
        $post_mock->post_title = 'Test';

        // Mock a term object.
        $term_mock = Mockery::mock( '\WP_Term' );
        $term_mock->name = 'Test';
        $term_mock->slug = 'test';
        $term_mock->taxonomy = 'category';
        $term_mock->description = '';

        $data = [
            'post' => $post_mock,
            'meta' => [
                [
                    'key'   => 'key1',
                    'value' => 'value1',
                ],
                [
                    'key'   => 'another_key',
                    'value' => 'another_value',
                ],
            ],
            'terms' => [
                [
                    'oopi_id' => $term_id,
                    'term'    => $term_mock,
                ]
            ],
            'attachments' => [
                [
                    'oopi_id' => $attachment_id,
                    'src'     => 'https://example.com/attachment_1.jpg',
                ]
            ],
        ];

        // Use the factory to create a post.
        $importable = PostFactory::create( $post_id, $data );

        $this->assertEquals( $post_id, $importable->get_oopi_id() );
        $this->assertCount( 2, $importable->get_meta() );
        $this->assertCount( 1, $importable->get_terms() );
        $this->assertCount( 1, $importable->get_attachments() );
    }

}