# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

## [1.8.0] - TBD
**Note that this release bumps the PHP minimum from 7.0 to 7.2.**

### Added
- Added: "Recommended Content" Block powered by Azure Personalizer (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#343](https://github.com/10up/classifai/pull/343)).
- "Classify Post" button in the Block Editor sidebar to process existing content (props [@iamdharmesh](https://github.com/iamdharmesh), [@thrijith](https://github.com/thrijith), [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#366](https://github.com/10up/classifai/pull/366)).

### Fixed
- Language Processing previewer now only loads properly within the Language Processing section (props [@Sidsector9](https://github.com/Sidsector9), [@iamdhamesh](https://github.com/iamdhamesh), [@cadic](https://github.com/cadic) via [#361](https://github.com/10up/classifai/pull/361)).
- Generate, Regenerate, and Scan buttons now work for newly uploaded media (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter), [@cadic](https://github.com/cadic) via [#364](https://github.com/10up/classifai/pull/364)).
- Admin JavaScript enqueue issues (props [@iamdharmesh](https://github.com/iamdharmesh), [@cadic](https://github.com/cadic) via [#372](https://github.com/10up/classifai/pull/372)).

### Changed
- Upgrade the Plugin Update Checker library, `yahnis-elsts/plugin-update-checker`, from 4.6 to 4.13 (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#365](https://github.com/10up/classifai/pull/365)).

### Security
- Bump `got` from 10.7.0 to 11.8.5 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@iamdharmesh](https://github.com/iamdharmesh) via [#371](https://github.com/10up/classifai/pull/371)).
- Bump `@wordpress/env` from 4.9.0 to 5.3.0 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@iamdharmesh](https://github.com/iamdharmesh) via [#371](https://github.com/10up/classifai/pull/371)).

## [1.7.3] - 2022-07-28
**Note that this release bumps the WordPress minimum from 5.0 to 5.6.**

### Added
- Scan and Smart Crop bulk actions have been added for media files, allowing you to bulk process existing content (props [@ShahAaron](https://github.com/ShahAaron), [@dinhtungdu](https://github.com/dinhtungdu), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#253](https://github.com/10up/classifai/pull/253)).
- Toggle to allow enabling/disabling language processing when content is updated (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#329](https://github.com/10up/classifai/pull/329)).
- Preview for Language Processing settings changes (props [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul) via [#351](https://github.com/10up/classifai/pull/351)).

### Changed
- Bump our minimum supported version of WordPress to 5.6 (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#329](https://github.com/10up/classifai/pull/329)).

### Fixed
- Ensure we support relative image paths (props [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh), [@dinhtungdu](https://github.com/dinhtungdu) via [#350](https://github.com/10up/classifai/pull/350)).

### Security
- Bump `terser` from 5.14.1 to 5.14.2 (props [@dependabot](https://github.com/apps/dependabot) via [#332](https://github.com/10up/classifai/pull/356)).

## [1.7.2] - 2022-06-27
### Added
- `classifai_should_register_save_post_handler` filter; allows modifying the registration conditions for the `SavePostHandler` class (props [@s3rgiosan](https://github.com/s3rgiosan), [@dkotter](https://github.com/dkotter) via [#341](https://github.com/10up/classifai/pull/341)).
- More robust PHP testing, including PHP 8 compatibility (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#328](https://github.com/10up/classifai/pull/328)).
- End-to-end testing with Cypress (props [@iamdharmesh](https://github.com/iamdharmesh), [@faisal-alvi](https://github.com/faisal-alvi) via [#334](https://github.com/10up/classifai/pull/334)).

### Changed
- Bump WordPress version "tested up to" 6.0 (props [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul) via [#346](https://github.com/10up/classifai/pull/346)).
- Updates in `Build Release` GitHub action (props [@iamdharmesh](https://github.com/iamdharmesh), [@dinhtungdu](https://github.com/dinhtungdu) via [#347](https://github.com/10up/classifai/pull/347)).

### Removed
- Removed the `pot` file and `vendor` directory from being version controlled (props [@dinhtungdu](https://github.com/dinhtungdu), [@iamdharmesh](https://github.com/iamdharmesh) via [#212](https://github.com/10up/classifai/pull/212)).

### Fixed
- Hook docs deployment (props [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9) via [#345](https://github.com/10up/classifai/pull/345)).

## [1.7.1] - 2022-04-25
### Added
- `classifai_post_statuses` filter; allows post statuses for content classification to be changed as required but would apply to all post types (props [@jamesmorrison](https://github.com/jamesmorrison), [@dkotter](https://github.com/dkotter) via [#310](https://github.com/10up/classifai/pull/310)).
- `classifai_post_statuses_for_post_type_or_id` filter; allows post statuses for content classification to be changed as required based on post type / post ID (props [@jamesmorrison](https://github.com/jamesmorrison), [@dkotter](https://github.com/dkotter) via [#310](https://github.com/10up/classifai/pull/310)).
- Implement `can_register()` method for `Classifai/Providers/Watson/NLU.php` (props [@thrijith](https://github.com/thrijith) via [#313](https://github.com/10up/classifai/pull/313)).
- Notice for deprecated IBM Watson `watsonplatform.net` NLU API endpoint (props [@rahulsprajapati](https://github.com/rahulsprajapati), [@jeffpaul](https://github.com/jeffpaul) via [#320](https://github.com/10up/classifai/pull/320)).
- CodeQL Analaysis code scanning and Dependency security scanning actions (props [@jeffpaul](https://github.com/jeffpaul) via [#314](https://github.com/10up/classifai/pull/314), [#336](https://github.com/10up/classifai/pull/336)).

### Changed
- Bump WordPress "tested up to" version 5.9 (props [@s3rgiosan](https://github.com/s3rgiosan), [@jeffpaul](https://github.com/jeffpaul) via [#327](https://github.com/10up/classifai/pull/327)).
- Normalize copy around Image Processing functions (props [@s3rgiosan](https://github.com/s3rgiosan), [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [Delfina Hoxha](https://www.linkedin.com/in/delfina-hoxha/), [@myBCN](https://github.com/myBCN), [@ajmaurya99](https://github.com/ajmaurya99) via [#325](https://github.com/10up/classifai/pull/325)).
- Port WP-CLI commands documentation into the [ClassifAI Developer Documentation site](https://10up.github.io/classifai/) (props [@ActuallyConnor](https://github.com/ActuallyConnor), [@jeffpaul](https://github.com/jeffpaul), [@faisal-alvi](https://github.com/faisal-alvi) via [](https://github.com/10up/classifai/pull/312)).

### Removed
- Unused `check_license_key` method from `Classifai/Providers/Watson/NLU.php` (props [@thrijith](https://github.com/thrijith) via [#313](https://github.com/10up/classifai/pull/313)).
- Remove unused `ClassifaiCommand->gc()` method, `ServicesManager->can_register()` method, and AWS Provider `Comprehend` class (props [@rahulsprajapati](https://github.com/rahulsprajapati), [@jamesmorrison](https://github.com/jamesmorrison), [@dinhtungdu](https://github.com/dinhtungdu), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#317](https://github.com/10up/classifai/pull/317)).

### Fixed
- Individual "Classify" action per post type (props [@mustafauysal](https://github.com/mustafauysal), [@cadic](https://github.com/cadic) via [#311](https://github.com/10up/classifai/pull/311)).
- Missing PHPUnit Polyfills library by adding `yoast/phpunit-polyfills:^1.0.0` dev package (props [@rahulsprajapati](https://github.com/rahulsprajapati) via [#319](https://github.com/10up/classifai/pull/319)).

### Security
- Bump `minimist` from 1.2.5 to 1.2.6 (props [@dependabot](https://github.com/apps/dependabot) via [#332](https://github.com/10up/classifai/pull/332)).

## [1.7.0] - 2021-08-26
### Added
- Automated Optical Character Recognition (OCR) scanning of multi-page PDF files adding text content to media description field (props [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter), [@phpbits](https://github.com/phpbits) via [#282](https://github.com/10up/classifai/pull/282)).
- ComputerVision::reset_settings() method (props [@ActuallyConnor](https://github.com/ActuallyConnor), [@dinhtungdu](https://github.com/dinhtungdu) via [#264](https://github.com/10up/classifai/pull/264)).
- `Update URI` header to ensure only legitimate ClassifAI updates are applied to this install (props [@jeffpaul](https://github.com/jeffpaul) via [#290](https://github.com/10up/classifai/pull/290)).
- Issue management automation via GitHub Actions (props [@jeffpaul](https://github.com/jeffpaul) via [#294](https://github.com/10up/classifai/pull/294)).

### Changed
- Update WP CLI command docs (props [@jeffpaul](https://github.com/jeffpaul) via [#259](https://github.com/10up/classifai/pull/259)).
- Update WPCS configuration from 1.3.1 to 1.3.2 (props [@dinhtungdu](https://github.com/dinhtungdu) via [#291](https://github.com/10up/classifai/pull/291)).
- Updated plugin icon, added banner (props [@blancahong](https://profiles.wordpress.org/blancahong/) via [#293](https://github.com/10up/classifai/pull/293)).
- Bump WordPress version "tested up to" 5.8 (props [@phpbits](https://github.com/phpbits), [@barneyjeffries](https://github.com/barneyjeffries) via [#302](https://github.com/10up/classifai/pull/302)).

### Fixed
- WordPress 5.6 `array_intersect_key` error (props [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu) via [#263](https://github.com/10up/classifai/pull/263)).
- WordPress 5.7 OCR block compatibility issue (props [@dinhtungdu](https://github.com/dinhtungdu), [@helen](https://github.com/helen) via [#275](https://github.com/10up/classifai/pull/275)).
- Update hooks priority for `wp_generate_attachment_metadata` to work with cloud storage providers (props [@thrijith](https://github.com/thrijith) via [#271](https://github.com/10up/classifai/pull/271)).
- Move `classifai_generate_image_alt_tags_source_url` filter to helper function (props [@thrijith](https://github.com/thrijith) via [#271](https://github.com/10up/classifai/pull/271)).
- Use `get_modified_image_source_url` where rescanning is done local file (props [@thrijith](https://github.com/thrijith) via [#271](https://github.com/10up/classifai/pull/271)).
- Updates to pass VIPCS check (props [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#287](https://github.com/10up/classifai/pull/287)).
- JavaScript error in Block Editor when ClassifAI has not been set up correctly (props [@junaidbhura](https://github.com/junaidbhura), [@dinhtungdu](https://github.com/dinhtungdu) via [#286](https://github.com/10up/classifai/pull/286)).
- Ensure Image Processing buttons in the media modal work when editing posts (props [@dinhtungdu](https://github.com/dinhtungdu), [@helen](https://github.com/helen) via [#295](https://github.com/10up/classifai/pull/295)).
- Hides the Scan Text checkbox field on the media edit page when OCR is disabled (props [@Sidsector9](https://github.com/Sidsector9), [@myBCN](https://github.com/myBCN), [@jeffpaul](https://github.com/jeffpaul) via [#299](https://github.com/10up/classifai/pull/299)).
- Issues with error messages not being displayed for 'Detect Text' feature (props [@Sidsector9](https://github.com/Sidsector9) via [#300](https://github.com/10up/classifai/pull/300)).

### Security
- Bump `ini` from 1.3.5 to 1.3.7 (props [@dependabot](https://github.com/dependabot) via [#262](https://github.com/10up/classifai/pull/262)).
- Bump `elliptic` from 6.5.3 to 6.5.4 (props [@dependabot](https://github.com/dependabot) via [#269](https://github.com/10up/classifai/pull/269)).
- Bump `y18n` from 4.0.0 to 4.0.1 (props [@dependabot](https://github.com/dependabot) via [#273](https://github.com/10up/classifai/pull/273)).
- Bump `ssri` from 6.0.1 to 6.0.2 (props [@dependabot](https://github.com/dependabot) via [#274](https://github.com/10up/classifai/pull/274)).
- Bump `lodash` from 4.17.20 to 4.17.21 (props [@dependabot](https://github.com/dependabot) via [#276](https://github.com/10up/classifai/pull/276)).
- Bump `hosted-git-info` from 2.8.8 to 2.8.9 (props [@dependabot](https://github.com/dependabot) via [#277](https://github.com/10up/classifai/pull/277)).
- Bump `browserslist` from 4.14.5 to 4.16.6 (props [@dependabot](https://github.com/dependabot) via [#283](https://github.com/10up/classifai/pull/283)).
- Bump `path-parse` from 1.0.6 to 1.0.7 (props [@dependabot](https://github.com/dependabot) via [#301](https://github.com/10up/classifai/pull/301)).

## [1.6.0] - 2020-11-02
### Added
- Automated Optical Character Recognition (OCR) scanning of screenshots and other imagery with `aria-describedby` semantic markup (props [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu), [@helen](https://github.com/helen), [@jeffpaul](https://github.com/jeffpaul) via [#228](https://github.com/10up/classifai/pull/228))
- Ability to smart crop existing images in WP Admin (props [@ShahAaron](https://github.com/ShahAaron), [@dinhtungdu](https://github.com/dinhtungdu), [@rickalee](https://github.com/rickalee) via [#252](https://github.com/10up/classifai/pull/252))
- WP-CLI `crop` command to smart crop images (props [@dinhtungdu](https://github.com/dinhtungdu), [@dkotter](https://github.com/dkotter), [@johnwatkins0](https://github.com/johnwatkins0) via [#236](https://github.com/10up/classifai/pull/236), [#254](https://github.com/10up/classifai/pull/254))
- Better error handling for manual scanning of alt text or image tags (props [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu) via [#231](https://github.com/10up/classifai/pull/231))
- `classifai_generate_image_alt_tags_source_url` filter to allow overriding of the image URL within `generate_image_alt_tags()` (props [@petenelson](https://github.com/petenelson), [@dinhtungdu](https://github.com/dinhtungdu) via [#217](https://github.com/10up/classifai/pull/217))

### Changed
- Updated from v1.0 to v3.0 of Azure Computer Vision Analyze API (props [@dkotter](https://github.com/dkotter), [@dinhtungdu](https://github.com/dinhtungdu), [@jeffpaul](https://github.com/jeffpaul) via [#244](https://github.com/10up/classifai/pull/244), [#255](https://github.com/10up/classifai/pull/255))
- Don't default to the `post` post type, if no other post types are selected for Language Processing (props [@dkotter](https://github.com/dkotter) via [#247](https://github.com/10up/classifai/pull/247))
- Don't process items if no Language Processing features are enabled (props [@dkotter](https://github.com/dkotter) via [#249](https://github.com/10up/classifai/pull/249))
- Image Processing metabox copy (props [@ActuallyConnor](https://github.com/ActuallyConnor), [@ryanwelcher](https://github.com/ryanwelcher), [@jeffpaul](https://github.com/jeffpaul) via [#214](https://github.com/10up/classifai/pull/214))
- Update admin menu icon color (props [@helen](https://github.com/helen) via [#258](https://github.com/10up/classifai/pull/258))
- Bump WordPress version "tested up to" 5.5.1 (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#237](https://github.com/10up/classifai/pull/237))
- Documentation, linting, and testing updates (props [@ryanwelcher](https://github.com/ryanwelcher), [@jeffpaul](https://github.com/jeffpaul), [@helen](https://github.com/helen), [@dinhtungdu](https://github.com/dinhtungdu) via [#204](https://github.com/10up/classifai/pull/204), [#215](https://github.com/10up/classifai/pull/215), [#226](https://github.com/10up/classifai/pull/226), [#239](https://github.com/10up/classifai/pull/239), [#251](https://github.com/10up/classifai/pull/251))

### Removed
- `Media` as option to select in Language Processing as Attachments are never processed (props [@dkotter](https://github.com/dkotter), [@ShahAaron](https://github.com/ShahAaron), [@dinhtungdu](https://github.com/dinhtungdu), [@jeffpaul](https://github.com/jeffpaul) via [#245](https://github.com/10up/classifai/issues/245))

### Fixed
- Smart cropping results not as expected (props [@dinhtungdu](https://github.com/dinhtungdu), [@oscarssanchez](https://github.com/oscarssanchez), [@ShahAaron](https://github.com/ShahAaron), [@jeffpaul](https://github.com/jeffpaul) via [@229](https://github.com/10up/classifai/pull/229))
- Sending largest image size possible when initiating a scan from the single edit screen (props [@dkotter](https://github.com/dkotter) via [#235](https://github.com/10up/classifai/pull/235))
- CDN image storage compatibility issue (props [@ShahAaron](https://github.com/ShahAaron), [@jeffpaul](https://github.com/jeffpaul) via [#250](https://github.com/10up/classifai/pull/250))
- Manual image scanning functions if automatic scanning is disabled (props [@dkotter](https://github.com/dkotter) via [#233](https://github.com/10up/classifai/pull/233))
- Issue where scan/rescan buttons did not appear in image modal upon first load (props [@dkotter](https://github.com/dkotter) via [#256](https://github.com/10up/classifai/pull/256))
- Prevent PHP notice if IBM Watson credentials are empty (props [@barryceelen](https://github.com/barryceelen), [@dinhtungdu](https://github.com/dinhtungdu), [@adamsilverstein](https://github.com/adamsilverstein) via [#206](https://github.com/10up/classifai/pull/206))
- Azure Computer Vision credentials saving and notification (props [@barryceelen](https://github.com/barryceelen), [@dinhtungdu](https://github.com/dinhtungdu) via [#207](https://github.com/10up/classifai/pull/207))
- `permission_callback` error on WordPress 5.5 (props [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#219](https://github.com/10up/classifai/pull/219))

### Security
- Bump `acorn` from 6.3.0 to 6.4.1 (props [@dependabot](https://github.com/dependabot) via [#203](https://github.com/10up/classifai/pull/203))
- Bump `lodash` from 4.17.15 to 4.17.19 (props [@dependabot](https://github.com/dependabot) via [#216](https://github.com/10up/classifai/pull/216))
- Bump `elliptic` from 6.5.1 to 6.5.3 (props [@dependabot](https://github.com/dependabot) via [#218](https://github.com/10up/classifai/pull/218))
- Bump `yargs-parser` from 13.1.1 to 13.1.2 (props [@dependabot](https://github.com/dependabot) via [#223](https://github.com/10up/classifai/pull/223))

## [1.5.1] - 2020-03-06
### Added
- Unit test coverage (props [@ryanwelcher](https://github.com/ryanwelcher) via [#198](https://github.com/10up/classifai/pull/198))
- `readme.txt` file to ensure plugin details surface in WP Admin (props [@jeffpaul](https://github.com/jeffpaul) via [#196](https://github.com/10up/classifai/pull/196))

### Changed
- Consolidated hook documentation and release workflows (props [@helen](https://github.com/helen) via [#192](https://github.com/10up/classifai/pull/192))

### Fixed
- Remove references to obsolete `process_image` method in favor of new functions (props [@johnwatkins0](https://github.com/johnwatkins0), [@helen](https://github.com/helen) via [#195](https://github.com/10up/classifai/pull/195))
- Hook documentation generator ([@helen](https://github.com/helen) via [#191](https://github.com/10up/classifai/pull/191))

## [1.5.0] - 2020-3-04
### Added
- Smart image cropping via Microsoft Azure Computer Vision (props [@johnwatkins0](https://github.com/johnwatkins0), [@Ritesh-patel](https://github.com/Ritesh-patel), [@daveross](https://github.com/daveross) [@rickalee](https://github.com/rickalee), [@dinhtungdu](https://github.com/dinhtungdu) via [#149](https://github.com/10up/classifai/pull/149))
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
- Image tagging via Azure Computer Vision (props [@ryanwelcher](https://github.com/ryanwelcher), [@helen](https://github.com/helen), [@jeffpaul](https://github.com/jeffpaul) via [#125](https://github.com/10up/classifai/pull/125))
- Search images by alt text and tags (props [@helen](https://github.com/helen), [@ryanwelcher](https://github.com/ryanwelcher) via [#134](https://github.com/10up/classifai/pull/134))
- Hooks to catch alt text and image tags being returned from Azure (props [@ryanwelcher](https://github.com/ryanwelcher), [@helen](https://github.com/helen), [@jeffpaul](https://github.com/jeffpaul) via [#125](https://github.com/10up/classifai/pull/125))
- Plugin debug information within WordPress's Site Health Info screen (props [@johnwatkins0](https://github.com/johnwatkins0), [@ryanwelcher](https://github.com/ryanwelcher), [@helen](https://github.com/helen), [@jeffpaul](https://github.com/jeffpaul) via [#108](https://github.com/10up/classifai/pull/108))
- Show a notice if you're running a development version of the plugin (props [@helen](https://github.com/helen), [@adamsilverstein](https://github.com/adamsilverstein), [@jeffpaul](https://github.com/jeffpaul) via [#144](https://github.com/10up/classifai/pull/144))

### Changed
- Enable comma delimited list of Post IDs in WP CLI command for Watson NLU bulk language processing (props [@adamsilverstein](https://github.com/adamsilverstein), [@helen](https://github.com/helen) via [#55](https://github.com/10up/classifai/pull/55))

### Fixed
- Provide backup behavior when full-sized image is greater than the maximum size accepted by Azure Computer Vision (props [@johnwatkins0](https://github.com/johnwatkins0), [@adamsilverstein](https://github.com/adamsilverstein), [@helen](https://github.com/helen), [@jeffpaul](https://github.com/jeffpaul) via [#110](https://github.com/10up/classifai/pull/110))
- Don't show the admin menu alert when NLU is unconfigured (props [@helen](https://github.com/helen), [@eflorea](https://github.com/eflorea) via [#142](https://github.com/10up/classifai/pull/142))

## [1.3.2] - 2019-07-24
### Fixed
- Only run Watson NLU when it's fully configured (props [@helen](https://github.com/helen), [@eflorea](https://github.com/eflorea) via [#103](https://github.com/10up/classifai/pull/103))
- NLU Settings backwards compatibility and WP-CLI command registration (props [@JayWood](https://github.com/JayWood), [@aaronjorbin](https://github.com/aaronjorbin), [@jeffpaul](https://github.com/jeffpaul), [@helen](https://github.com/helen) via [#96](https://github.com/10up/classifai/pull/96))
- Avoid JS errors and inaccurate data representation of `_classifai_error` meta (props [@johnwatkins0](https://github.com/johnwatkins0) via [#106](https://github.com/10up/classifai/pull/106))
- Resolve sudden Travis test failures (props [@jeffpaul](https://github.com/jeffpaul), [@johnwatkins0](https://github.com/johnwatkins0) via [#107](https://github.com/10up/classifai/pull/107))

### Changed
- Documentation updates (props [@jeffpaul](https://github.com/jeffpaul), [@dustinrue](https://github.com/dustinrue) via [#89](https://github.com/10up/classifai/pull/89), [#90](https://github.com/10up/classifai/pull/90), [#94](https://github.com/10up/classifai/pull/94), and [#97](https://github.com/10up/classifai/pull/97))

## [1.3.1] - 2019-06-13
### Fixed
- Specify and handle minimum PHP version support (props [@helen](https://github.com/helen) via [#84](https://github.com/10up/classifai/pull/84))

## [1.3.0] - 2019-06-06
### Added
- Support for automatic image alt text with Microsoft Azure's Computer Vision API (props [@ryanwelcher](https://github.com/ryanwelcher), [@helen](https://github.com/helen) via [#46](https://github.com/10up/classifai/pull/46))
- Azure seutp and configuration details to docs (props [@jeffpaul](https://github.com/jeffpaul) via [#71](https://github.com/10up/classifai/pull/71))
- Composer `type` and `license` attributes (props [@christianc1](https://github.com/christianc1), [@helen](https://github.com/helen) via [#57](https://github.com/10up/classifai/pull/57))
- WordPress version support badge (props [@adamsilverstein](https://github.com/adamsilverstein), [@jeffpaul](https://github.com/jeffpaul) via [#67](https://github.com/10up/classifai/pull/67))

### Changed
- Settings page split into separate Language and Image Processing settings pages (props [@ryanwelcher](https://github.com/ryanwelcher), [@helen](https://github.com/helen) via [#46](https://github.com/10up/classifai/pull/46))

### Security
- Bump `js-yaml` from 3.12.1 to 3.13.1 (props [@dependabot](https://github.com/dependabot) via [#74](https://github.com/10up/classifai/pull/74))

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

[Unreleased]: https://github.com/10up/classifai/compare/trunk...develop
[1.8.0]: https://github.com/10up/classifai/compare/1.7.3...1.8.0
[1.7.3]: https://github.com/10up/classifai/compare/1.7.2...1.7.3
[1.7.2]: https://github.com/10up/classifai/compare/1.7.1...1.7.2
[1.7.1]: https://github.com/10up/classifai/compare/1.7.0...1.7.1
[1.7.0]: https://github.com/10up/classifai/compare/1.6.0...1.7.0
[1.6.0]: https://github.com/10up/classifai/compare/1.5.1...1.6.0
[1.5.1]: https://github.com/10up/classifai/compare/1.5.0...1.5.1
[1.5.0]: https://github.com/10up/classifai/compare/1.4.0...1.5.0
[1.4.0]: https://github.com/10up/classifai/compare/1.3.2...1.4.0
[1.3.2]: https://github.com/10up/classifai/compare/1.3.1...1.3.2
[1.3.1]: https://github.com/10up/classifai/compare/1.3.0...1.3.1
[1.3.0]: https://github.com/10up/classifai/compare/1.2.1...1.3.0
[1.2.1]: https://github.com/10up/classifai/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/10up/classifai/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/10up/classifai/compare/4bf845...1.1.0
[1.0.0]: https://github.com/10up/classifai/commit/4bf8456816c73c509001ad4cad03a6fcdcb7e478
