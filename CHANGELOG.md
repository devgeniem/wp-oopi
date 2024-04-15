# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [Released]

## [1.3.2] - 2024-04-15

### Changed
- Fix issue with nonexistent attachments. Fixes the situation where the importer might fail with the image that doesn't exist.

## [1.3.1] - 2024-01-29

### Changed
- Enabled PHP 8.0 and PHP 8.1 usage #18

### Fixed
- Fix PHP 8 deprecation. #25

## [1.3.0] - 2023-08-01
- Fixed the issue https://github.com/devgeniem/wp-oopi/issues/23 If OOPI_IGNORE_SSL has been enabled and the certificate on the source site is invalid, the exif_imagetype() function cannot be used in the AttachmentImporter.

## [1.2.1] - 2023-01-31
- Adds `parse_url( $url, PHP_URL_PATH )` to parse attachment importable's url.

## [1.2.0] - 2022-04-27

### Added
- Filters `oopi_before_save_post_acf` and `oopi_before_save_post_acf/type={field_type}`.

## [1.1.0] - 2022-02-21

### Added
- Schedule log cleaner cronjob on plugin activation. The cronjob deletes rows from log table older than the threshold set.

### Fixed
- Fixed autoloading, added composer test + more #17

## [1.0.1] - 2022-02-03

### Fixed

- Fixed inserting ACF data to attachments #19
- Fixed autoloading, added composer test + more #17

## [1.0.0] - 2020-08-27

### Changed
- A complete plugin code base overhaul. Refer to the [example post](./docs/examples/example-post.php) and the [README](./README.md) for changes in implementing importers with OOPI.

## [0.3.5] - 2021-04-28

### Fixed
- Fixed post parent handling when using WordPress's post id.

## [0.3.4] - 2020-11-16

### Fixed
- Fixed term handling in case of an already reserved slug.

## [0.3.3] - 2020-10-21

### Fixed
- Added null check for term localization.

### Changed
- Fixed the term translation and ACF repeater field example code.

## [0.3.1] - 2020-07-01

### Added

- Add an option to ignore/skip SSL Certificate verification by defining `OOPI_IGNORE_SSL` as true.

## [0.3.0] - 2020-06-26

### Added
- New typed classes for taxonomy term and language data.
  - Geniem\Oopi\Term
  - Geniem\Oopi\Language
- Localize newly created terms if language data is added for the Post object.
- Try to prevent the post name from getting a number suffix
  when translating posts with Polylang by updating the post data
  with the original post name after setting the language.
- Add a language agnostic term finding method to Storage class. Use it to find terms by slug.

### Changed
- Allow language data to be set as an associative array or an object. Map data into a Geniem\Oopi\Language object.
- Simplify Oopi id handling in get_post_id_by_oopi_id().
- The `i18n` is now deprecated, but still functioning if set. The data is mapped into a Geniem\Oopi\Language object.
- Taxonomy term data must contain an `oopi_id` for identification.

## [0.2.0] - 2020-05-20

### Fixed
- Rename old hooks to better reflect the actual functionality:
    - `wp_oopi_pre_post_save` -> `oopi_pre_insert_post`
    - `wp_oopi_after_post_save` -> `oopi_after_insert_post`
- Polylang integration is run on the 'wp_loaded' hook. This prevents various warnings by allowing PLL to fully load itself before running integrations.

### Added
- New hooks for running code before and after saving an Oopi post.

### Changed
- Rename all Post class hooks by removing the `wp_` prefix.

## [0.1.2] - 2020-04-28

### Fixed
- Fixed WP insert post data filter removing when saving the Oopi object.

## [0.1.1] - 2020-03-11

### Changed
- Handle errors occurring within `wp_insert_post()`.
- Fix text domains in various texts.

## [0.1.0] - 2020-09-03

### Changed
- Master id parsing in PLL locale saving.

### Added
- A filter to prevent Polylang from synchronizin Oopi identification data between translations.
- A method in the Storage class for getting the Oopi identificator key.

## [0.0.0] - 2020-01-31

### Added
- The initial release.
