![geniem-github-banner](https://cloud.githubusercontent.com/assets/5691777/14319886/9ae46166-fc1b-11e5-9630-d60aa3dc4f9e.png)

# Oopi

**Oopi** is a WordPress importer plugin enabling importing WordPress data from external sources through an object-oriented functional API.

## Installation

Install the plugin with Composer by first adding the private repository and then requiring the package:

```
composer config repositories.devgeniem/wp-oopi git git@github.com:devgeniem/wp-oopi.git
composer require devgeniem/wp-oopi
```
Then activate the plugin from the WordPress dashboard or with the WP-CLI.

```
wp plugin activate wp-oopi
```

## Importing post objects

The plugin provides a functional API for importing various data types. You can think of the importer as an integration layer between your custom data source and the WordPress database. Your job is to format the data to meet the importer object specification. If you are lucky and your external data source can provide the data in the importer data format, your job is as simple as decoding the data into a PHP object and then passing it through the API.

An import is a two step process. First you must set the data for the importer `\Geniem\Oopi\Post` object instance and then you call its save method to store the object data into the WordPress database.

An example of importing a single post can be found [here](docs/examples/example-post.php).

### \Geniem\Oopi\Post - Methods

#### __construct() `public`

To start a new import process call the Post class constructor and pass a unique id for it. This creates a new instance of the class and identifies it. If this is an update, the WP post matching the id is fetched and the post object data is loaded as default values for the import. *To ensure the time values are updating they are unset from the post object at this point.*

##### Parameters

- `$oopi_id` *(string) (Required)* An id uniquely identifies the object in the external data source.

##### Example usage

```php
$post = new \Geniem\Oopi\Post( 'my_id_1234' );
```



#### set_data() `public`

The first step in the import process is to set the data for the importer. This funtion takes a full importer object as a parameter, validates all fields and sets the data into the corresponding class properties. To check if the data is valid after setting it, you can call the `get_errors()` which will return an array of occurred errors.

##### Parameters

- `$raw_post` *(object) (Required)* An object containing needed `\Geniem\Oopi\Post` class properties.
  - `post` *(object) (Required)* The basic [WP post](https://codex.wordpress.org/Class_Reference/WP_Post) object data as a `stdClass` object.
  - `attachments` *(array) (Optional)* An array of attachment objects containing:
    - `id` *(string) (Required)* An unique id identifying the object in the external data source.
    - `src` *(string) (Required)* The source from which to upload the image into WordPress. 
      - *The plugin currently supports only image files!*
    - `alt` *(string) (Optional)* The alt text for the image. This is saved into postmeta.
    - `caption` *(string) (Optional)* The file caption text.
    - `description` *(string) (Optional)* The file description text.
  - `meta` *(object) (Optional)* An object where all the keys correspond to meta keys and values correspond to meta values.
  - `taxonomies` *(array) (Optional)* An array containing either-or:
    - *(Geniem\Oopi\Term)* Oopi Term object.
      - _If the Oopi term holds a WP_Term object, importing will override existing term data._
    - *(array|object)* Raw data will be mapped into a Term object. 
      - `oopi_id` *(string) (Required)* All terms must contain an id. 
      - `slug` *(string) (Required)* The taxonomy term slug.
      - `name` *(string) (Required)* The taxonomy term display name.
      - `taxonomy` *(string) (Required)* The taxonomy name, for example `category`.
  - `acf` *(array) (Optional)* An array of Advanced Custom Fields data objects containing:
    - `type` *(string) (Required)* The ACF field type ([types](https://www.advancedcustomfields.com/resources/#field-types)).
    - `key` *(string) (Required)* The ACF field key. This must be the unique key defined for the field.
    - `value` *(mixed) (Required)* The data value matching the field type specifications.
  - `language` *(Geniem\Oopi\Language|object|array) (Optional)* Localization information in an Oopi Language object or raw data. Raw data will be converted into a Language instance.

#### Example usage

```php
$post->set_data( $my_raw_post_data );
```

##### Example data in JSON format

```json
{
  "post": {
    "post_title": "The title",
    "post_content": "This is a new post and it is awesome!",
    "post_excerpt": "This is a new post..."
  },
  "meta": {
    "my_meta_key": "My meta value.",
    "my_meta_key2": 1234
  },
  "attachments": [
    {
      "mime_type": "image/jpg",
      "id": "123456",
      "alt": "Alt text is stored in postmeta.",
      "caption": "This is the post excerpt.",
      "description": "This is the post content.",
      "src": "http://upload-from-here.com/123456.jpg",
    }
  ],
  "taxonomies": [
    {
      "slug": "my-term",
      "taxonomy": "post_tag"
    }
  ],
}
```

#### save() `public`

Run this function after setting the data for the importer object. This function saves all set data into WordPress database. Before any data is stored into the database the current `Post` object is validated and it throws an `Geniem\Oopi\Exception\PostException` if any errors have occurred. After all data is saved into the database the instance is validated again and any save errors throw the same expection. If no errors occurred, the WordPress post id is returned.

##### Parameters

- `$force_save` *(boolean) (Optional)* Set this to `true` skip validation and force saving. You can create custom validations through multiple hooks or by manually inspecting error with by getting them with the `get_errors()` function. Defaults to `false`.

## Plugin settings

The `\Geniem\Oopi\Settings\` class is used to set and load all plugin settings. It uses the following settings that are overridable with corresponding constants.

### Available settings

- Setting key `id_prefix`, constant `OOPI_ID_PREFIX`, default value `'oopi_id_'`.
- Setting key `attachment_prefix`, constant `OOPI_ATTACHMENT_PREFIX`, default value `'oopi_attachment_'`.
- Setting key `log_errors`, constant `OOPI_LOG_ERRORS`, default value `false`.
- Setting key `transient_key`, constant `OOPI_TRANSIENT_KEY`, default value `'oopi_'`.
- Setting key `transient_expiration`, constant `OOPI_TRANSIENT_EXPIRATION`, default value `HOUR_IN_SECOND`.
- Setting key `tmp_folder`, constant `OOPI_TMP_FOLDER`, default value `'/tmp/'`.
- Setting key `table_name`, constant `OOPI_TABLE_NAME`, default value `geniem_importer_log`.
- Setting key `log_status_ok`, constant `OOPI_LOG_STATUS_OK`, default value `'OK'`.
- Setting key `log_status_fail`, constant `OOPI_LOG_STATUS_FAIL`, default value `'FAIL'`.

### Accessing settings

Example usage:

```php
$oopi_id_prefix = \Geniem\Oopi\Settings::get( 'id_prefix' );
```

### Overriding settings

To override the transient expiration time set the following constant before the setting is used the first time.

```php
define( 'OOPI_TRANSIENT_EXPIRATION', MONTH_IN_SECONDS );
```

To ignore SSL Certificate verification errors in your importer, you can define `OOPI_IGNORE_SSL` as true.
```php
define( 'OOPI_IGNORE_SSL', true );
```
See `\Geniem\Oopi\Post::insert_attachment_from_url`.

## Logging

The plugin creates a custom table into the WordPress database called `oopi_log`. This table holds log entries of all import actions and contains the following columns:

- `id` Log entry id.
- `oopi_id` The importer object id.
- `post_id` The WordPress post id of the importer object. Stored only if the `save()` is run successfully.
- `import_date_gmt` A GMT timestamp of the import date in MySQL datetime format.
- `data` The importer object data containing all properties including errors.
- `status` The import status: `OK|FAIL`.

### Rollback

The log provides a rollback feature. If an import fails the importer tries to roll back the previous successful import. If no previous imports with the `OK` status are found, the imported object is set into `draft` state to prevent front-end users from accessing posts with malformed data.

To disable the rollback feature set the `OOPI_ROLLBACK_DISABLE` constant with a value of `true`.

## Changelog

[CHANGELOG.md](CHANGELOG.md)

## Contributors

-  [Geniem](https://github.com/devgeniem)
-  [Ville Siltala](https://github.com/villesiltala)
-  [Timi-Artturi Mäkelä](https://github.com/Liblastic)
