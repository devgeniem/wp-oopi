<?php
/**
 * An example of importing a post object.
 */

use Geniem\Oopi\Attribute\Language;
use Geniem\Oopi\Importable\PostImportable;

// The unique id for the post.
$oopi_id = 'my_custom_id_1234';

// Create a post object.
$post = new PostImportable( $oopi_id );

// In this example this post is an english version
// of the post with the Oopi id 'the_default_object_56'.
$post->set_language( new Language( $post, 'en', 'the_default_object_56' ) );

// Set the basic post data as an associative array and cast it to object.
$post->set_post( new WP_Post(
    (object) [
        'post_title'   => 'The post title',
        'post_name'    => sanitize_title( 'The post title' ),
        'post_content' => 'The post main content HTML.',
        'post_excerpt' => 'The excerpt text of the post.',
    ]
) );

// The array of attachments.
$post->set_attachments(
    [
        [
            'filename'    => '123456.jpg',
            'mime_type'   => 'image/jpg',
            'id'          => '123456',
            'alt'         => 'Alt text is stored in postmeta.',
            'caption'     => 'This is the post excerpt.',
            'description' => 'This is the post content.',
            'src'         => 'http://upload-from-here.com/123456.jpg',
        ],
    ]
);

// Postmeta data as key-value pairs.
$post->set_meta(
    [
        'key1'        => 'value1',
        'another_key' => 'another_value',
        'integer_key' => 123,
    ]
);

// Create a category term.
$oopi_term = new Term( 'my-translated-term' );
$oopi_term->set_data(
    [
        'slug'     => 'my-translated-term',
        'name'     => 'My translated term',
        'taxonomy' => 'category',
    ]
);
// Set the term language and set the master id.
// In this case 'my-original-term' would be the Oopi id
// of the previously imported term in the main language.
// The term's slug must be unique.
$oopi_term->set_language( new Language( 'en', 'my-original-term' ) );

// Set the term into the array.
$post->set_taxonomies(
    [
        $oopi_term,
    ]
);

// Advanced Custom Fields data.
$post->set_acf(
    [
        [
            'key'   => 'repeater_field_key',
            'value' => [
                [
                    'sub_field_key'         => '...',
                    'another_sub_field_key' => '...',
                ],
                [
                    'sub_field_key'         => '...',
                    'another_sub_field_key' => '...',
                ],
            ],
        ],
        [
            'key'   => 'single_field_key',
            'value' => '...',
        ],
        [
            'key'   => 'attachment_field_key',
            'value' => 'oopi_attachment_123456',
        ],
    ]
);

// Try to save the post.
try {
    // If the data was invalid or errors occur while saving the post into the dabase, an exception is thrown.
    $post->save();
}
catch ( \Geniem\Oopi\Exception\PostException $e ) {
    foreach ( $e->get_errors() as $scope => $errors ) {
        foreach ( $errors as $key => $message ) {
            error_log( "Importer error in $scope: " . $message );
        }
    }
}
