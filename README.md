![geniem-github-banner](https://cloud.githubusercontent.com/assets/5691777/14319886/9ae46166-fc1b-11e5-9630-d60aa3dc4f9e.png)

[![Build Status](https://travis-ci.org/devgeniem/wp-oopi.svg?branch=master)](https://travis-ci.org/devgeniem/wp-oopi) [![Latest Stable Version](https://poser.pugx.org/devgeniem/wp-oopi/v/stable)](https://packagist.org/packages/devgeniem/wp-oopi) [![Total Downloads](https://poser.pugx.org/devgeniem/wp-oopi/downloads)](https://packagist.org/packages/devgeniem/wp-oopi) [![Latest Unstable Version](https://poser.pugx.org/devgeniem/wp-oopi/v/unstable)](https://packagist.org/packages/devgeniem/wp-oopi) [![License](https://poser.pugx.org/devgeniem/wp-oopi/license)](https://packagist.org/packages/devgeniem/wp-oopi)

# OOPI

**OOPI** (an acronym from "object-oriented, programming and importer) is a WordPress plugin providing an object-oriented library to ease and give structure for importing data into WordPress from external sources.

## Installation

Install the plugin with Composer:

```
composer require devgeniem/wp-oopi
```

Then activate the plugin from the WordPress dashboard or with the WP-CLI.

```
wp plugin activate wp-oopi
```

## How does it work?

The plugin provides functionalities for importing various data types. You can think of the importer as an integration layer between your custom data source and the WordPress database. Your job is to format the data into OOPI objects by manually using class constructors or the provided helper factories. If your external data source can provide the data in the OOPI data format, your job is as simple as decoding the data into a PHP object and then passing it for a `create` method in a [importable factory](./src/Factory/Importable).

OOPI provides the saving process for your data by using WordPress functions to interact with the database. With OOPI you get a standardized way of building developer friendly importers and it helps you write better code by providing important features such as error handling and logging.

## Importing posts

An import is a two step process. First step is to create an importable object. If it is a post you are importing, then you create a `\Geniem\Oopi\Importable\PostImportable` object. You can use the class constructor directly or use the easy way and use a provided [factory method](./src/Factory/Importable/PostFactory.php). The `PostFactory::create` method takes a unique OOPI id and a full importer object as a parameter, validates all fields and converts the data into strictly typed objects. During the import process, the OOPI id is stored in (post) meta and it is used to identify your imported object after it is initially created. Subsequent imports with the same OOPI id will update the matching post.

After your data is set, the actual importing is done by calling the importable's `import()` method. This method uses composition to call the actual import method of an importer. All importers implement the `\Geniem\Oopi\Intefaces\Importer` inteface and all importables should contain an importer. The `import` method will handle saving all the data attached to the importable object into WordPress database.

Importables allow changing the import process by passing an importer object for the class constructor. If none is set, all importables get [a default importer](./src/Importer) provided by OOPI during the instantiation.

### Example usage

See [the post example](docs/examples/example-post.php).

## Importing terms

You can import terms along your posts. But if you require more control over your term importing, for example to prevent importing posts after erroneous term imports, you can use the OOPI importing process for terms.

To get started, create an importable by instantiating a `\Geniem\Oopi\Importable\TermImportable` directly and use setters to set your data or use the `\Geniem\Oopi\Factory\Importable\TermFactory::create` method. After your data is set, import the term object by calling its `import` method. Just like with a post imortable, this will call the `import` method of the attached term importer.

### Example usage

See [the term example](docs/examples/term-example.php).

## Importing attachments

Attachments can be imported along post importables, but if required, you can import attachments in a dedicated process. Importing attachments follows the same process as posts and terms. Create an attachment importable by instantiating the `\Geniem\Oopi\Importable\AttachmentImportable` class or by calling the `\Geniem\Oopi\Factory\Importable\AttachmentFactory::create` method. After setting all the data, call the `import` method of the importable. This will trigger the import process by calling the `import` method in the attached importer.

Attachments are problematic when importing data into WordPress. OOPI attempts to make it as simple as possible by handling the file transfering from the provided source _(`src` attribute)_, but bear in mind, downloading and uploading files from external sources and into WordPress will take time. With OOPI, you are fully in control of your importing process and you might run into timeouts if your server configurations are not properly set to handle your workloads.

_Note! OOPI supports only image MIME types currently._

## Customizing the import process

To customize the term importing process, create your own importable class extending the corresponding importer provided by OOPI or by directly implementing the `\Geniem\Oopi\Interfaces\Importable` interface. For example, to customize the attachment import process, extend the `\Geniem\Oopi\Importer\AttachmentImporter` and override its methods as needed. To override the default importer instance in an importable, instantiate your custom importer and pass it for your importable constructor or factory.

## Attributes

Importables have attributes that are *saved* during the import process. Each attribute class should have a corresponding saver for handling the database interactions. For example, post meta is an attribute you can attach to post importables. When the post is imported, the post meta attribute is saved with the post meta saver using the WordPress post meta functions.

See the list of all available attributes [here](./src/Attribute).

### Language attribute

OOPI supports localization with the `\Geniem\Oopi\Attribute\Language` attribute. Currently, OOPI provides a language saver for [Polylang](https://fi.wordpress.org/plugins/polylang/) and is able to localize posts, terms and attachments.

The properties for a language attribute are:

- `importable` `(Required)` - The related importable object.
- `locale` `(Required)` - The locale string _(e.q. 'en')_.
- `main_oopi_id` `(Optional)` - The OOPI id of importable in the main language. It is used to map translations.
- `saver` `(Optional)` - The language saver.

To automatically map your importables as translations of one another, set the `main_oopi_id` for importables in other than the main language. For example, if your WordPress site's main language is English, set the OOPI id of the English importable as the main OOPI id for its translations. Import the main language always first for OOPI to be able to map the main object for other languages.

### AcfField attribute

OOPI supports importing [Advanced Custom Fields](https://fi.wordpress.org/plugins/advanced-custom-fields/) field data with the `\Geniem\Oopi\Attribute\AcfField` attribute. The `\Geniem\Oopi\Attribute\Saver\AcfFieldSaver` controls the saving process and can handle post and term fields. Note that the field saving is limited by the functionalities of the ACF's [`update_field` function](https://www.advancedcustomfields.com/resources/update_field/).

## Factories

### `\Geniem\Oopi\Factory\Importable\PostFactory`

#### `::create( $oopi_id, $data )`

- `$oopi_id` `(string) (Required)` The unique external identifier for your importable.
- `$data` `(array|object) (Required)` An array/object containing the following keys:
  - `post` `(object) (Required)` A [`WP_Post`](https://codex.wordpress.org/Class_Reference/WP_Post) instance or an array/object containing the keys and values for `WP_Post` class properties.
  - `attachments` `(array) (Optional)` An array of attachment objects containing:
    - `oopi_id` `(string) (Required)` An unique id identifying the object in the external data source.
    - `src` `(string) (Required)` The source from which to upload the image into WordPress.
    - `alt` `(string) (Optional)` The alt text for the image. This is saved into postmeta.
    - `caption` `(string) (Optional)` The file caption text.
    - `description` `(string) (Optional)` The file description text.
    - `is_thumbnail` `(bool) (Optional)` Defines if the attachment should set as the post thumbnail.
  - `meta` `(array) (Optional)` An array of arrays or objects containing:
    - `key` `(string) (Required)` The meta key.
    - `value` `(mixed) (Required)` The meta value.
  - `terms` `(array) (Optional)` An array containing either-or:
    - `(Geniem\Oopi\Importable\TermImportable)` OOPI Term importable.
      - _If the OOPI term importable holds a WP_Term object, importing will override existing term data._
    - `(array|object)`
      - `oopi_id` `(string) (Required)` All terms must contain an id.
      - `term` `WP_Term|object|array (Optional)` An object/array containing properties of a WP_Term object or a `WP_Term` object. If set, data for the Term importable will be mapped from the object.
      - `slug` `(string) (Required)` The taxonomy term slug. The term slugs must be unique, ie. they can not collide between different language versions.
      - `name` `(string) (Required)` The taxonomy term display name.
      - `taxonomy` `(string) (Required)` The taxonomy name, for example `category`.
  - `acf` `(array) (Optional)` An array of Advanced Custom Fields data objects containing:
    - `type` `(string) (Required)` The ACF field type ([types](https://www.advancedcustomfields.com/resources/#field-types)).
    - `key` `(string) (Required)` The ACF field key. This must be the unique key defined for the field.
    - `value` `(mixed) (Required)` The data value matching the field type specifications.
  - `language` `(Geniem\Oopi\Attribute\Language|object|array) (Optional)` Localization information in an OOPI Language object or raw data. Raw data will be converted into a Language instance.

### `\Geniem\Oopi\Factory\Importable\TermFactory`

See: [TermFactory](./src/Factory/Importable/TermFactory.php).

### `\Geniem\Oopi\Factory\Importable\AttachmentFactory`

See: [AttachmentFactory](./src/Factory/Importable/AttachmentFactory.php).

## Plugin settings

The `\Geniem\Oopi\Settings\` class is used to set and load all plugin settings. It uses the following settings that are overridable with constants.

### Available settings

- Setting key `id_prefix`, constant `OOPI_ID_PREFIX`, default value `'oopi_id_'`.
- Setting key `attachment_prefix`, constant `OOPI_ATTACHMENT_PREFIX`, default value `'oopi_attachment_'`.
- Setting key `log_errors`, constant `OOPI_LOG_ERRORS`, default value `false`.
- Setting key `transient_key`, constant `OOPI_TRANSIENT_KEY`, default value `'oopi_'`.
- Setting key `transient_expiration`, constant `OOPI_TRANSIENT_EXPIRATION`, default value `HOUR_IN_SECOND`.
- Setting key `tmp_folder`, constant `OOPI_TMP_FOLDER`, default value `'/tmp/'`. Used for handling attachments.
- Setting key `table_name`, constant `OOPI_TABLE_NAME`, default value `geniem_importer_log`.
- Setting key `log_status_ok`, constant `OOPI_LOG_STATUS_OK`, default value `'OK'`.
- Setting key `log_status_fail`, constant `OOPI_LOG_STATUS_FAIL`, default value `'FAIL'`.
- Setting key `cron_interval_clean_log`, constant `OOPI_CRON_INTERVAL_CLEAN_LOG`, default `'daily'`.

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

### Error log

OOPI uses a generalized error handling. An instance of the error handler is passed as a dependecy to all instances attached to an importable. If an error occurs, the error handler logs _(by default logging is enabled)_ and stores the error in the handler instance. If there where errors during the import process, an exception is thrown for you to catch.

You can customize the error handling process by overriding the default error handler. To do so, create a class implementing the `Geniem\Oopi\Interfaces\ErrorHandler` interface and override the default handler instance with your instance by passing it for factories or class constructors.

### Import log

The plugin creates a custom table into the WordPress database called `oopi_log`. This table holds log entries of imports _(currently only for posts)_ and contains the following columns:

- `id` Log entry id.
- `oopi_id` The importer object id.
- `wp_id` The WordPress post id of the importer object. Stored only if the `save()` is run successfully.
- `import_date_gmt` A GMT timestamp of the import date in MySQL datetime format.
- `data` The importer object data containing all properties including errors.
- `status` The import status: `OK|FAIL`.

### Rollback

The log provides a rollback feature. If an import fails the importer tries to roll back the previous successful import. If no previous imports with the `OK` status are found, the imported object is set into `draft` state to prevent front-end users from accessing posts with malformed data.

To disable the rollback feature set the `OOPI_ROLLBACK_DISABLE` constant with a value of `true`.

### Log cleanup

The plugin registers a log cleaner cronjob on plugin activation. The cronjob deletes all rows with OK status by `wp_id` except the latest one. This enables keeping the log table clean while maintaining full support for rollback feature.

The cronjob is run with `'daily'` interval by default, it can be changed with `OOPI_CRON_INTERVAL_CLEAN_LOG` constant. Cronjob scheduling can be disabled by defining the constant as `false`.

## Tests

### Local tests

The plugin code is tested using PHPUnit. Local test can be run using the provided Docker container with Docker Compose. The following command will build the container and start it. The container's CMD script will run PHPUnit once and then set up [pywathc](https://pypi.org/project/pywatch/) to watch changes in the `./tests` directory. If changes are made, test are rerun.

```
$ docker compose up
```

If you need to debug the tests, the container comes with [Xdebug 3](https://xdebug.org/) for step debugging. You need to set up PHPUnit using the provided docker container as a remote interpreter. For this, use the `php` service in the Docker Compose file and the PHPUnit executable `vendor/bin/phpunit`. The full path to the executable inside the container is `/usr/src/app/vendor/bin/phpunit`.

### Travis CI

This repository also contains a GitHub integration for [Travic CI](https://travis-ci.org/). All commits to the `main` branch will be automatically tested with Travis. 

## Changelog

[CHANGELOG.md](CHANGELOG.md)

## Contributors

-  [Geniem](https://github.com/devgeniem)
-  [Ville Siltala](https://github.com/villesiltala)
-  [Timi-Artturi Mäkelä](https://github.com/Liblastic)
