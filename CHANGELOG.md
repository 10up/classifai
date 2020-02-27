# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

## [1.5.0] - 2020-02-27
### Added
- Smart image cropping via Microsoft Azure Computer Vision (props [@johnwatkins0](https://github.com/johnwatkins0), [@rickalee](https://github.com/rickalee), [@dinhtungdu](https://github.com/dinhtungdu), [@Ritesh-patel](https://github.com/Ritesh-patel) via [#149](https://github.com/10up/classifai/pull/149))
- Process Existing Images with Microsoft Azure Computer Vision (props [@ryanwelcher](https://github.com/ryanwelcher), [@dinhtungdu](https://github.com/dinhtungdu), [@jeffpaul](https://github.com/jeffpaul) via [#135](https://github.com/10up/classifai/pull/135))
- Bulk processing for posts, pages, and images (props [@dinhtungdu](https://github.com/dinhtungdu), [@ryanwelcher](https://github.com/ryanwelcher), [@jeffpaul](https://github.com/jeffpaul) via [#178](https://github.com/10up/classifai/pull/178))
- WP-CLI command to bulk process images (props [@dinhtungdu](https://github.com/dinhtungdu), [@eflorea](https://github.com/eflorea), [@ryanwelcher](https://github.com/ryanwelcher), [@jeffpaul](https://github.com/jeffpaul) via [#177](https://github.com/10up/classifai/pull/177))
- ClassifAI settings and result of latest service provider requests to Site Health Info screen (props [@dinhtungdu](https://github.com/dinhtungdu), [@jeffpaul](https://github.com/jeffpaul) via [#180](https://github.com/10up/classifai/pull/180))
- ClassifAI icon to WP Admin menu (props [@dinhtungdu](https://github.com/dinhtungdu), [@JackieKjome](https://github.com/JackieKjome), [@jeffpaul](https://github.com/jeffpaul) via [#173](https://github.com/10up/classifai/pull/173))
- [WP Acceptance](https://github.com/10up/wpacceptance) end-to-end acceptance tests (props [@dinhtungdu](https://github.com/dinhtungdu), [@adamsilverstein](https://github.com/adamsilverstein), [@ryanwelcher](https://github.com/ryanwelcher), [@jeffpaul](https://github.com/jeffpaul) via [#179](https://github.com/10up/classifai/pull/179))

### Changed
- Bump WordPress version "tested up to" 5.3 (props [@ryanwelcher](https://github.com/ryanwelcher) via [#160](https://github.com/10up/classifai/pull/160))
- IBM Watson credentials settings UX (props [@dinhtungdu](https://github.com/dinhtungdu), [@ryanwelcher](https://github.com/ryanwelcher), [@helen](https://github.com/helen) via [#175](https://github.com/10up/classifai/pull/175))
- PHP version error message when attempting to install ClassifAI with PHP lower than v7.0 (props [@dinhtungdu](https://github.com/dinhtungdu), [@jeffpaul](https://github.com/jeffpaul) via [#174](https://github.com/10up/classifai/pull/174))
- Documentation updates (props [@jeffpaul](https://github.com/jeffpaul), [@kant](https://github.com/kant), [@dinhtungdu](https://github.com/dinhtungdu) via [#151](https://github.com/10up/classifai/pull/151), [#153](https://github.com/10up/classifai/pull/153), [#170](https://github.com/10up/classifai/pull/170), [#181](https://github.com/10up/classifai/pull/181), [#184](https://github.com/10up/classifai/pull/184))

### Removed
- Double slashes in IBM Watson JavaScript URL (props [@dinhtungdu](https://github.com/dinhtungdu) via [#168](https://github.com/10up/classifai/pull/168))

### Fixed
- Issue where pages are not scanned by Language Processing (props [@dinhtungdu](https://github.com/dinhtungdu), [@ryanwelcher](https://github.com/ryanwelcher) via [#164](https://github.com/10up/classifai/pull/164))
- Properly saves protected meta in Gutenberg (props [@dinhtungdu](https://github.com/dinhtungdu) via [#172](https://github.com/10up/classifai/pull/172))
- Duplicate notification and wrong settings link after activation (props [@dinhtungdu](https://github.com/dinhtungdu), [@eflorea](https://github.com/eflorea) via [#169](https://github.com/10up/classifai/pull/169))
- PHP Coding Standards updates (props [@mmcachran](https://github.com/mmcachran) via [#156](https://github.com/10up/classifai/pull/156))
- Integration test update (props [@johnwatkins0](https://github.com/johnwatkins0) via [#162](https://github.com/10up/classifai/pull/162))

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
[1.5.0]: https://github.com/10up/classifai/compare/1.4.0...1.5.0
[1.4.0]: https://github.com/10up/classifai/compare/1.3.2...1.4.0
[1.3.2]: https://github.com/10up/classifai/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/10up/classifai/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/10up/classifai/compare/1.2.1...1.3.0
[1.2.1]: https://github.com/10up/classifai/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/10up/classifai/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/10up/classifai/compare/4bf845...1.1.0
[1.0.0]: https://github.com/10up/classifai/commit/4bf8456816c73c509001ad4cad03a6fcdcb7e478
