<?php
/**
 * An example of importing a post object.
 */

use Geniem\Oopi\Attribute\Language;
use Geniem\Oopi\Importable\PostImportable;
use Geniem\Oopi\Importable\TermImportable;

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
        'post_type'    => 'post',
        'post_content' => 'The post main content HTML.',
        'post_excerpt' => 'The excerpt text of the post.',
    ]
) );

// The array of attachments.
$post->set_attachments(
    [
        [
            'oopi_id'     => '123456',
            'filename'    => '123456.png',
            'mime_type'   => 'image/png',
            'alt'         => 'Alt text is stored in postmeta.',
            'caption'     => 'This is the post excerpt.',
            'description' => 'This is the post content.',
            'src'         =>
                'https://cloud.githubusercontent.com/assets/5691777/14319886/9ae46166-fc1b-11e5-9630-d60aa3dc4f9e.png',
        ],
    ]
);

// Postmeta data as key-value pairs.
$post->set_meta(
    [
        [
            'key'   => 'key1',
            'value' => 'value1',
        ],
        [
            'key'   => 'another_key',
            'value' => 'another_value',
        ],
        [
            'key'   => 'integer_key',
            'value' => 123,
        ],
    ]
);

// Create a category term.
$oopi_term = new TermImportable( 'my-translated-term' );
$oopi_term->set_slug( 'my-translated-term' )
          ->set_name( 'My translated term' )
          ->set_taxonomy( 'category' );

// Set the term language and set the main id.
// In this case 'default-language-term' would be the Oopi id
// of the previously imported term in the main language.
$oopi_term->set_language( new Language( $oopi_term, 'en', 'default-language-term' ) );

// Set the term into the array.
$post->set_terms(
    [
        $oopi_term,
    ]
);

// Advanced Custom Fields data.
$post->set_acf(
    [
        [
            'key'   => 'single_field_key',
            'value' => 'single value',
        ],
        [
            // For ACF images the value should be the OOPi id for referencing the correct attachment object.
            'key'   => 'attachment_field_key',
            'value' => '123456', // This is the OOPI id of the attachment.
            'type'  => 'image', // Note the different type.
        ],
    ]
);

// Try to save the post.
try {
    // If the data was invalid or errors occur while saving the post into the dabase, an exception is thrown.
    echo intval( $post->import() );
}
catch ( \Geniem\Oopi\Exception\PostException $e ) {
    // For this example we just dump and log the errors.
    foreach ( $e->get_errors() as $scope => $errors ) {
        foreach ( $errors as $error ) {
            $message = $error['message'];
            $data    = $error['data'];

            var_dump( $data ); // phpcs:ignore
            error_log( "Importer error in $scope: " . $message ); // phpcs:ignore
        }
    }
}