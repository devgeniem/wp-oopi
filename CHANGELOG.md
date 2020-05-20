# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

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
