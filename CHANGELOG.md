# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

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

## [1.0.0]
- Initial closed source release
