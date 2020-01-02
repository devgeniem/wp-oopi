<?php
/**
 * An example of importing a post object.
 */

$data = new stdClass();

// Set the basic post data as an associative array and cast it to object.
$data->post = (object) [
    'post_title'   => 'The post title',
    'post_content' => 'The post main content HTML.',
    'post_excerpt' => 'The excerpt text of the post.',
];

// The array of attachments.
$data->attachments = [
    [
        'filename'    => '123456.jpg',
        'mime_type'   => 'image/jpg',
        'id'          => '123456',
        'alt'         => 'Alt text is stored in postmeta.',
        'caption'     => 'This is the post excerpt.',
        'description' => 'This is the post content.',
        'src'         => 'http://upload-from-here.com/123456.jpg',
    ],
];

// Postmeta data as key-value pairs.
$data->meta = [
    'key1'        => 'value1',
    'another_key' => 'another_value',
    'integer_key' => 123,
];

// Advanced Custom Fields data.
$data->acf = [
    'name'  => 'repeater_field_key',
    'value' => [
        [
            'name'  => 'repeater_value_1',
            'value' => '...',
        ],
        [
            'name'  => 'repeater_value_2',
            'value' => '...',
        ],
    ],
    [
        'name'  => 'single_field_key',
        'value' => '...',
    ],
    [
        'name'  => 'attachment_field_key',
        'value' => 'oopi_attachment_123456',
    ],
];

// In this example this post is an english version
// of the post with the Oopi id 56.
$data->i18n = [
    'locale' => 'en',
    'master' => 'oopi_id_56',
];

// Create a new instance by a unique id.
$api_id = 'my_custom_id_1234';
$post   = new \Geniem\Oopi\Post( $api_id );

// Set all the data for the post.
$post->set_data( $data );

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
