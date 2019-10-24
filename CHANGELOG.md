# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

## [1.4.0] - 2019-09-26
### Added
- Image tagging via Azure Computer Vision (props @ryanwelcher via #125)
- Search images by alt text and tags (props @helen via #134)
- Hooks to catch alt text and image tags being returned from Azure (props @ryanwelcher via #125)
- Plugin debug information within WordPress's Site Health Info screen (props @johnwatkins0 via #108)
- Show a notice if you're running a development version of the plugin (props @helen via #144)

### Changed
- Enable comma delimited list of Post IDs in WP CLI command for Watson NLU bulk language processing (props @adamsilverstein via #55)

### Fixed
- Provide backup behavior when full-sized image is greater than the maximum size accepted by Azure Computer Vision (props @johnwatkins0 via #110)
- Don't show the admin menu alert when NLU is unconfigured (props @helen via #142)

## [1.3.2] - 2019-07-24
### Fixed
- Only run Watson NLU when it's fully configured (props @helen, @eflorea via #103)
- NLU Settings backwards compatibility and WP-CLI command registration (props @JayWood, @aaronjorbin via #96)
- Avoid JS errors and inaccurate data representation of `_classifai_error` meta (props @johnwatkins0 via #106)
- Resolve sudden Travis test failures (props @jeffpaul via #107)

### Changed
- Documentation updates (props @jeffpaul, @dustinrue via #89, #90, #94, and #97)

## [1.3.1] - 2019-06-13
### Fixed
- Specify and handle minimum PHP version support (props @helen via #84)

## [1.3.0] - 2019-06-06
### Added
- Support for automatic image alt text with Microsoft Azure's Computer Vision API (props @ryanwelcher, @helen via #46)
- Azure seutp and configuration details to docs (props @jeffpaul via #71)
- Composer `type` and `license` attributes (props @christianc1 via #57)
- WordPress version support badge (props @adamsilverstein, @jeffpaul via #67)

### Changed
- Settings page split into separate Language and Image Processing settings pages (props @ryanwelcher, @helen via #46)

### Security
- Bump js-yaml from 3.12.1 to 3.13.1 (props @dependabot via #74)

## [1.2.1] - 2019-04-25
### Added
- Free registration for in-admin updates

### Fixed
- Run init at a later priority to be sure that most other callbacks run first
- Clean up docs references

## [1.2.0] - 2019-03-21
### Added
- Initial public release with a new name! 🎉
- Gutenberg support
- Admin support for Concepts classification
- Clearer settings page
- Alert if plugin is not set up with credentials yet
- Tests and linting and documentation, oh my

## [1.1.0] - 2018-10-30
### Added
- Taxonomy mapping support
- Admin notice on API errors

## [1.0.0] - 2018-07-24
- Initial closed source release

[Unreleased]: https://github.com/10up/classifai/compare/master...develop
[1.4.0]: https://github.com/10up/classifai/compare/1.3.2...1.4.0
[1.3.2]: https://github.com/10up/classifai/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/10up/classifai/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/10up/classifai/compare/1.2.1...1.3.0
[1.2.1]: https://github.com/10up/classifai/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/10up/classifai/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/10up/classifai/compare/4bf845...1.1.0
[1.0.0]: https://github.com/10up/classifai/commit/4bf8456816c73c509001ad4cad03a6fcdcb7e478
