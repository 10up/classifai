# Changelog

All notable changes to this project will be documented in this file, per [the Keep a Changelog standard](http://keepachangelog.com/).

## [Unreleased] - TBD

## [3.2.0] - 2024-12-18

**Prior to updating, please read: this release of ClassifAI rearchitects how the settings pages are built, from a standard PHP approach to using React components. If you've created custom Features or Providers or added your own custom settings, you'll need to update your code to work in this new structure. See our [documentation](https://10up.github.io/classifai/tutorial-useful-snippets.html) for examples.**

**Also note that this release bumps the WordPress minimum from 6.1 to 6.5.**

### Added

- New Feature, Smart 404, which provides the ability to render recommended content on a 404 page, based on the URL path someone is trying to access (props [@dkotter](https://github.com/dkotter), [@berkod](https://github.com/berkod), [@iamdharmesh](https://github.com/iamdharmesh) via [#801](https://github.com/10up/classifai/pull/801)).
- New Feature, Term Cleanup, which provides the ability to compare all terms from specific taxonomies and provide recommendations for similar terms that can be merged together (props [@dkotter](https://github.com/dkotter), [@berkod](https://github.com/berkod), [@iamdharmesh](https://github.com/iamdharmesh) via [#815](https://github.com/10up/classifai/pull/815)).
- New feature and service provider onboarding process implemented in React (props [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9), [Kate Rickard](https://uk.linkedin.com/in/katerickard), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#771](https://github.com/10up/classifai/pull/771), [#824](https://github.com/10up/classifai/pull/824)).
- OpenAI ChatGPT as a Provider for the Descriptive Text Generator Feature (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#828](https://github.com/10up/classifai/pull/828)).
- Chrome AI as a new text generation Provider, allowing completely on-device AI usage. Note this is still an experimental feature in Chrome (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh), [@faisal-alvi](https://github.com/faisal-alvi) via [#819](https://github.com/10up/classifai/pull/819)).
- New filter, `classifai_openai_moderation_model`, allowing you to change the moderation model (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#811](https://github.com/10up/classifai/pull/811)).

### Changed

- Update the enable helper text for most of the Features to be more descriptive (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#807](https://github.com/10up/classifai/pull/807)).
- Use the new OpenAI Moderation model, `omni-moderation-latest`, in our moderation Feature (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#811](https://github.com/10up/classifai/pull/811)).
- Migrate from the Azure AI Vision v3.2 API to the v4.0 API for the Descriptive Text Generator, Image Tags Generator and Image Text Extraction Features (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#829](https://github.com/10up/classifai/pull/829)).
- Bump WordPress "tested up to" version to 6.7 (props [@s3rgiosan](https://github.com/s3rgiosan), [@jeffpaul](https://github.com/jeffpaul) via [#823](https://github.com/10up/classifai/pull/823)).
- Bump WordPress minimum from 6.1 to 6.5 (props [@s3rgiosan](https://github.com/s3rgiosan), [@jeffpaul](https://github.com/jeffpaul) via [#823](https://github.com/10up/classifai/pull/823)).

### Fixed

- Ensure that the Classification Feature suggests or sets terms for enabled taxonomies only (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#805](https://github.com/10up/classifai/pull/805)).
- Ensure all strings have translator comments (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#806](https://github.com/10up/classifai/pull/806)).
- Ensure post type and taxonomy options are filterable via existing filters in the new React settings (props [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#831](https://github.com/10up/classifai/pull/831)).
- Addressed all issues raised by running PHPStan (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#808](https://github.com/10up/classifai/pull/808)).

### Security

- Bump `axios` from 1.6.7 to 1.7.4 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#792](https://github.com/10up/classifai/pull/792)).
- Bump `webpack` from 5.90.3 to 5.94.0 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#796](https://github.com/10up/classifai/pull/796)).
- Bump `body-parser` from 1.20.2 to 1.20.3, `express` from 4.19.2 to 4.21.0, `ws` from 7.5.10 to 8.18.0, `send` from 0.18.0 to 0.19.0 and `serve-static` from 1.15.0 to 1.16.2 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#809](https://github.com/10up/classifai/pull/809)).
- Bump `@wordpress/scripts` from 27.9.0 to 30.6.0 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#830](https://github.com/10up/classifai/pull/830)).
- Bump `express` from 4.21.0 to 4.21.2, `@wordpress/e2e-test-utils-playwright` from 0.26.0 to 1.14.0 and removes `cookie` (props [@dependabot[bot]](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#832](https://github.com/10up/classifai/pull/832)).

### Developer

- Deprecate the old settings panel in favor of the new React-based approach (props [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#771](https://github.com/10up/classifai/pull/771)).
- Re-organize the `assets` directory (props [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh) via [#804](https://github.com/10up/classifai/pull/804)).
- Added support for WordPress Playground for easy testing of the plugin (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#814](https://github.com/10up/classifai/pull/814)).
- Added PHPStan into our workflow with the ability to run that locally and in a GitHub Action (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#808](https://github.com/10up/classifai/pull/808)).
- Add the WordPress Plugin Check GitHub Action (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#806](https://github.com/10up/classifai/pull/806)).
- Update GitHub Action Workflows to be more efficient and ensure all Actions are up-to-date (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#810](https://github.com/10up/classifai/pull/810)).
- Add plugin banner image to the README; remove table of contents (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#798](https://github.com/10up/classifai/pull/798)).

## [3.1.1] - 2024-08-06

### Changed

- Switch from using the `gpt-3.5-turbo` model to the new `gpt-4o-mini` model for any OpenAI text generation requests. Increase our context window from 16,385 tokens to 128,000 tokens (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#785](https://github.com/10up/classifai/pull/785)).

### Fixed

- Update a image URLs that no longer exist, fixing an issue with authentication in the Azure AI Vision Provider (props [@faisal-alvi](https://github.com/faisal-alvi), [@jamespstyles](https://github.com/jamespstyles), [@dkotter](https://github.com/dkotter) via [#787](https://github.com/10up/classifai/pull/787)).
- Ensure we show a proper error notice if the Azure AI Vision Provider fails to authenticate (props [@faisal-alvi](https://github.com/faisal-alvi), [@jamespstyles](https://github.com/jamespstyles), [@dkotter](https://github.com/dkotter) via [#787](https://github.com/10up/classifai/pull/787)).

## [3.1.0] - 2024-07-15

### Added

- Amazon Polly as a Provider for the Text-to-Speech Feature (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#734](https://github.com/10up/classifai/pull/734)).
- OpenAI as a Provider for the Text-to-Speech Feature (props [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#747](https://github.com/10up/classifai/pull/747)).
- Azure OpenAI Embeddings as a Provider for the Classification Feature (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#764](https://github.com/10up/classifai/pull/764)).
- "Generate image" button to media upload components (props [@Sidsector9](https://github.com/Sidsector9), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#749](https://github.com/10up/classifai/pull/749)).
- Feature to refresh Content Resizing results without closing the results modal (props [@Sidsector9](https://github.com/Sidsector9), [@jeffpaul](https://github.com/jeffpaul), [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter) via [#774](https://github.com/10up/classifai/pull/774)).
- Filter hook `classifai_ms_computer_vision_scan_image_timeout` to increase image processing timeout (props [@Sidsector9](https://github.com/Sidsector9), [@QAharshalkadu](https://github.com/QAharshalkadu), [@dkotter](https://github.com/dkotter) via [#754](https://github.com/10up/classifai/pull/754)).
- New filter `classifai_feature_classification_pre_save_results` that allows you to filter the classification results before they're saved, either modifying those results or running custom save routines (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#762](https://github.com/10up/classifai/pull/762)).
- New filters allowing the ability to change some of the OpenAI Embedding variables, like model or dimensions (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#758](https://github.com/10up/classifai/pull/758)).
- New filter `classifai_normalize_content_before_strip_all_tags` that allows you to filter the content before stripping all tags (props [@CacheMeOwside](https://github.com/CacheMeOwside), [@dkotter](https://github.com/dkotter) via [#775](https://github.com/10up/classifai/pull/775)).
- New filter `classifai_openai_embeddings_max_terms` to change the number of terms to process per job during Embeddings classification (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#779](https://github.com/10up/classifai/pull/779)).
- Higher timeout for Azure OpenAI Embedding requests (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#776](https://github.com/10up/classifai/pull/776)).
- "Testing" section in the `CONTRIBUTING.md` file (props [@kmgalanakis](https://github.com/kmgalanakis), [@jeffpaul](https://github.com/jeffpaul) via [#763](https://github.com/10up/classifai/pull/763)).

### Changed

- Update from the `text-embedding-ada-002` to the `text-embedding-3-small` model for the OpenAI Embeddings Provider. Note this requires regenerating all existing embeddings for comparisons to work properly. Also worth mentioning you'll want to check your threshold settings to ensure that works well with these changes, as we're seeing lower threshold scores with this new model (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#758](https://github.com/10up/classifai/pull/758)).
- When generating embeddings, ensure we chunk our content down and reduce our vector dimensions to 512 (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#758](https://github.com/10up/classifai/pull/758)).
- Increase the amount of terms we consider when running comparisons using the OpenAI Embeddings Provider from 500 to 5000 (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#758](https://github.com/10up/classifai/pull/758)).
- Allow the Embeddings Provider to be used by Features other than Classification (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#766](https://github.com/10up/classifai/pull/766)).
- Hide the Provider's configuration help text when the Provider is already configured (props [@kmgalanakis](https://github.com/kmgalanakis), [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#770](https://github.com/10up/classifai/pull/770)).
- `Select` label is changed to `Replace` within Content Resizing suggestions modal (props [@Sidsector9](https://github.com/Sidsector9), [@jeffpaul](https://github.com/jeffpaul), [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter) via [#774](https://github.com/10up/classifai/pull/774)).
- Content Resizing modal title is updated from static to dynamic depending on the type of resizing (props [@Sidsector9](https://github.com/Sidsector9), [@jeffpaul](https://github.com/jeffpaul), [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter) via [#774](https://github.com/10up/classifai/pull/774)).
- Add the ability to send chunked content in a single request to the Embedding API (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#776](https://github.com/10up/classifai/pull/776)).
- Embeddings classification now uses background processing to improve performance. This is done by including the Action Scheduler package (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#779](https://github.com/10up/classifai/pull/779)).
- Bump WordPress "tested up to" version to 6.6 (props [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#751](https://github.com/10up/classifai/pull/751)).
- Replaced `lee-dohm/no-response` with `actions/stale` to help with closing no-response/stale issues (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#751](https://github.com/10up/classifai/pull/751)).
- Update the `@10up/cypress-wp-utils` package to 0.3.0 (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#760](https://github.com/10up/classifai/pull/760)).

### Fixed

- Timeout issue with processing `.png` images of large file sizes (props [@Sidsector9](https://github.com/Sidsector9), [@QAharshalkadu](https://github.com/QAharshalkadu), [@dkotter](https://github.com/dkotter) via [#754](https://github.com/10up/classifai/pull/754)).
- Ensure the classification admin preview functionality loads for non built-in Providers (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#762](https://github.com/10up/classifai/pull/762)).
- Fix some undefined PHP variable warnings when running automatic classification (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#758](https://github.com/10up/classifai/pull/758)).
- Ensure the `Excerpt length` setting for the Excerpt Generation Feature can be changed (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#767](https://github.com/10up/classifai/pull/767)).
- Strip off superscript and subscript tags in content for text to speech generation (props [@CacheMeOwside](https://github.com/CacheMeOwside), [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#775](https://github.com/10up/classifai/pull/775)).
- Ensure we chunk content correctly, keeping each chunk roughly the same size (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#776](https://github.com/10up/classifai/pull/776)).

### Security

- Bump `follow-redirects` from 1.15.5 to 1.15.6 and `jsdoc` from 3.6.11 to 4.0.2 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter) via [#750](https://github.com/10up/classifai/pull/750)).
- Bump `webpack-dev-middleware` from 5.3.3 to 5.3.4 and `taffydb` from 2.6.2 to 2.7.3 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#752](https://github.com/10up/classifai/pull/752)).
- Bump `express` from 4.18.2 to 4.19.2 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@faisal-alvi](https://github.com/faisal-alvi) via [#753](https://github.com/10up/classifai/pull/753)).
- Bump `braces` from 3.0.2 to 3.0.3 and `ws` from 7.5.9 to 7.5.10 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#780](https://github.com/10up/classifai/pull/780)).

## [3.0.1] - 2024-03-07
### Fixed
- Ensure we only pass in array data to our `merge_settings` method (props [@dkotter](https://github.com/dkotter), [@ajaxthemestudios](https://github.com/ajaxthemestudios), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#733](https://github.com/10up/classifai/pull/733)).
- Handle scenario where non-array data is passed into our descriptive text generator feature (props [@dkotter](https://github.com/dkotter), [@ajaxthemestudios](https://github.com/ajaxthemestudios), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#733](https://github.com/10up/classifai/pull/733)).
- Address a fatal error that occurs if `composer install` hasn't been run (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#740](https://github.com/10up/classifai/pull/740)).
- Ensure we properly account for `null` values when merging our saved settings with our default settings (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#741](https://github.com/10up/classifai/pull/741)).

## [3.0.0] - 2024-02-29
**Note that this is a major release of ClassifAI that restructures most of the codebase and will have some breaking changes. If you're extending ClassifAI in any way, please ensure you fully test those integrations prior to running this update on production. For more details on what is changing, see the [migration guide](https://10up.github.io/classifai/tutorial-migration-guide-v2-to-v3.html).**

### Added
- New Moderation Feature that utilizes the OpenAI Moderation API to moderate comments (props [@kirtangajjar](https://github.com/kirtangajjar), [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#531](https://github.com/10up/classifai/pull/531)).
- Added the option to use Google's Gemini API as a Provider for the Title Generation, Excerpt Generation and Content Resizing Features (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#700](https://github.com/10up/classifai/pull/700)).
- Added the option to use Azure OpenAI as a Provider for the Title Generation, Excerpt Generation and Content Resizing Features (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#716](https://github.com/10up/classifai/pull/716)).
- Role and user-based access control for all Features (props [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9) via [#635](https://github.com/10up/classifai/pull/635)).
- Ability to preview which terms will be added before they get added when using the Embeddings provider (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#709](https://github.com/10up/classifai/pull/709)).
- Show error message if generating excerpts or titles fails in the Classic Editor (props [@dkotter](https://github.com/dkotter), [@faisal-alvi](https://github.com/faisal-alvi) via [#688](https://github.com/10up/classifai/pull/688)).
- Tool to automatically migrate settings from the old settings structure to the new feature-first settings structure (props [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh), [@qasumitbagthariya](https://github.com/qasumitbagthariya),  [@dkotter](https://github.com/dkotter) via [#711](https://github.com/10up/classifai/pull/711)).
- Migration guide for the feature-first refactoring (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#726](https://github.com/10up/classifai/pull/726)).
- Repo Automator GitHub Action to automate repo operations (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#681](https://github.com/10up/classifai/pull/681)).

### Changed
- Major refactoring of the plugin to a feature-first architecture (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#611](https://github.com/10up/classifai/pull/611)).
- Update the Azure AI Vision Image Analyze API from v3.0 to v3.2 and the Azure AI Vision Smart Cropping API from v3.1 to v3.2. Note that we recommend lowering the threshold values for the Descriptive Text Generator Feature to 55% for best results (props [@kmgalanakis](https://github.com/kmgalanakis), [@dkotter](https://github.com/dkotter), [@sksaju](https://github.com/sksaju), [@iamdharmesh](https://github.com/iamdharmesh) via [#559](https://github.com/10up/classifai/pull/559)).
- Update from using DALL·E 2 to using DALL·E 3 in our Image Generation Feature (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#717](https://github.com/10up/classifai/pull/717)).
- Refactor ClassifAI onboarding to work with the new feature-first refactoring (props [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9) via [#642](https://github.com/10up/classifai/pull/642)).
- Ensure the Classification functionality works the same for all Providers (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#709](https://github.com/10up/classifai/pull/709)).
- Introduce a filter to add new arguments to the Image Generation REST route, instead of registering that route twice (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#727](https://github.com/10up/classifai/pull/727)).
- If on a multisite install, when handling user access based on role, if a Super Admin does not have a specific role on a site, treat that user as an administrator (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@gsarig](https://github.com/gsarig), [@iamdharmesh](https://github.com/iamdharmesh) via [#689](https://github.com/10up/classifai/pull/689)).
- Remove the "Enable role-based access" and "Enable user-based access" settings options and instead set those to be enabled by default (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#692](https://github.com/10up/classifai/pull/692)).
- Update minimum node version to 20 (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#722](https://github.com/10up/classifai/pull/722)).
- Updated E2E tests to work with the new feature-first refactoring (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#653](https://github.com/10up/classifai/pull/653)).

### Fixed
- Ensure the classification popup works after feature-first refactoring (props [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#634](https://github.com/10up/classifai/pull/634)).
- Ensure we only run image updates once (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9), [@sksaju](https://github.com/sksaju) via [#677](https://github.com/10up/classifai/pull/677)).
- Ensure the Classification preview works as expected after feature-first refactoring (props [@dkotter](https://github.com/dkotter), [@QAharshalkadu](https://github.com/QAharshalkadu), [@Sidsector9](https://github.com/Sidsector9) via [#679](https://github.com/10up/classifai/pull/679)).
- Ensure all `WP_CLI` commands work after the feature-first refactoring (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#682](https://github.com/10up/classifai/pull/682)).
- Ensure the jQuery UI Dialog CSS is loaded properly (props [@dkotter](https://github.com/dkotter), [@faisal-alvi](https://github.com/faisal-alvi) via [#687](https://github.com/10up/classifai/pull/687)).
- Ensure the classification button has proper bottom spacing (props [@dkotter](https://github.com/dkotter), [@QAharshalkadu](https://github.com/QAharshalkadu), [@faisal-alvi](https://github.com/faisal-alvi) via [#694](https://github.com/10up/classifai/pull/694)).
- Load the dashicon font when the Text to Speech block is used to ensure icons display (props [@dkotter](https://github.com/dkotter), [@QAharshalkadu](https://github.com/QAharshalkadu), [@iamdharmesh](https://github.com/iamdharmesh) via [#695](https://github.com/10up/classifai/pull/695)).
- Ensure the Text to Speech block is rendered for non-logged in users (props [@dkotter](https://github.com/dkotter), [@QAharshalkadu](https://github.com/QAharshalkadu), [@iamdharmesh](https://github.com/iamdharmesh) via [#695](https://github.com/10up/classifai/pull/695)).
- Ensure the IBM Watson NLU credentials toggle link works properly in the ClassifAI onboarding (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#697](https://github.com/10up/classifai/pull/697)).
- Ensure the connection status shows properly in the ClassifAI onboarding (props [@Sidsector9](https://github.com/Sidsector9), [@QAharshalkadu](https://github.com/QAharshalkadu), [@dkotter](https://github.com/dkotter) via [#702](https://github.com/10up/classifai/pull/702)).
- Ensure the "Extract text from PDF" row action works properly in the media list table view (props [@iamdharmesh](https://github.com/iamdharmesh), [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@dkotter](https://github.com/dkotter) via [#706](https://github.com/10up/classifai/pull/706)).
- Fix fatal error if both the Classification Feature and Moderation Features are active (props [@dkotter](https://github.com/dkotter), [@QAharshalkadu](https://github.com/QAharshalkadu), [@iamdharmesh](https://github.com/iamdharmesh) via [#709](https://github.com/10up/classifai/pull/709)).
- Ensure the stand-alone Generate Image page works if the Media Library is loaded in list mode (props [@dkotter](https://github.com/dkotter), [@qasumitbagthariya](https://github.com/qasumitbagthariya), [@Sidsector9](https://github.com/Sidsector9) via [#712](https://github.com/10up/classifai/pull/712)).

### Removed
- Subscriber from the list of allowed roles (props [@dkotter](https://github.com/dkotter), [@ankitguptaindia](https://github.com/ankitguptaindia), [@Sidsector9](https://github.com/Sidsector9) via [#690](https://github.com/10up/classifai/pull/690)).
- `AccessControl` class as it is no longer in use after the feature-first refactoring (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#680](https://github.com/10up/classifai/pull/680)).
- Type declaration from the `attachment_is_pdf` parameter (props [@iamdharmesh](https://github.com/iamdharmesh), [@peterwilsoncc](https://github.com/peterwilsoncc), [@dkotter](https://github.com/dkotter) via [#719](https://github.com/10up/classifai/pull/719)).
- Eliminate all dead code that is no longer needed after the feature-first refactoring (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#678](https://github.com/10up/classifai/pull/678)).

### Deprecated
- Show deprecation notice for the Azure AI Personalizer provider, as this will be removed in a coming release (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#698](https://github.com/10up/classifai/pull/698)).

### Security
- Protect against potential prompt injection when using OpenAI's ChatGPT (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#683](https://github.com/10up/classifai/pull/683)).
- Bump `ip` from 1.1.8 to 1.1.9 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@iamdharmesh](https://github.com/iamdharmesh) via [#721](https://github.com/10up/classifai/pull/721)).

## [2.5.1] - 2024-01-11
### Changed
- Switch from using the Completions API to the Models API to verify credentials (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh) via [#654](https://github.com/10up/classifai/pull/654)).
- Update `10up/phpcs-composer` to version 3.0.0 (props [@dkotter](https://github.com/dkotter), [@faisal-alvi](https://github.com/faisal-alvi) via [#641](https://github.com/10up/classifai/pull/641)).

### Fixed
- Ensure that the "Classify" row/bulk action is visible only to users who have access to it (props [@iamdharmesh](https://github.com/iamdharmesh), [@ankitguptaindia](https://github.com/ankitguptaindia), [@dkotter](https://github.com/dkotter) via [#647](https://github.com/10up/classifai/pull/647)).
- Check for the `default` array key before we access it (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#650](https://github.com/10up/classifai/pull/650)).
- Address all new PHPCS issues (props [@dkotter](https://github.com/dkotter), [@faisal-alvi](https://github.com/faisal-alvi) via [#641](https://github.com/10up/classifai/pull/641)).

### Security
- Bump `tj-actions/changed-files` from 37 to 41 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#649](https://github.com/10up/classifai/pull/649)).
- Bump `follow-redirects` from 1.15.3 to 1.15.4 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#657](https://github.com/10up/classifai/pull/657)).

## [2.5.0] - 2023-12-13
**Note that this release bumps the WordPress minimum from 5.8 to 6.1.**

### Added
- Ability to control access to each feature based on user role or by individual users, allowing users to opt out of features they don't want (props [@iamdharmesh](https://github.com/iamdharmesh), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#606](https://github.com/10up/classifai/pull/606)).
- New manual classification mode that allows you to easily select which AI suggested terms you want to add (props [@faisal-alvi](https://github.com/faisal-alvi), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#609](https://github.com/10up/classifai/pull/609)).
- Enable/disable toggle option for the "Classify content", "Text to Speech" and "Recommended content" features (props [@iamdharmesh](https://github.com/iamdharmesh), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#614](https://github.com/10up/classifai/pull/614)).
- New setting option for the IBM Watson classification feature to allow you to classify content within existing terms only (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@faisal-alvi](https://github.com/faisal-alvi), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#620](https://github.com/10up/classifai/pull/620)).
- Threshold settings added for taxonomies in the OpenAI Embeddings classification feature (props [@faisal-alvi](https://github.com/faisal-alvi), [@timatron](https://github.com/timatron), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#621](https://github.com/10up/classifai/pull/621)).
- Ability to preview the suggested terms for the OpenAI Embeddings classification feature (props [@faisal-alvi](https://github.com/faisal-alvi), [@jeffpaul](https://github.com/jeffpaul), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#622](https://github.com/10up/classifai/pull/622)).
- Post autosave when a generated title is used or when a paragraph of text is resized to allow for Revisions-based rollbacks (props [@iamdharmesh](https://github.com/iamdharmesh), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#626](https://github.com/10up/classifai/pull/626)).

### Changed
- Bump WordPress minimum from 5.8 to 6.1 (props [@faisal-alvi](https://github.com/faisal-alvi), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#609](https://github.com/10up/classifai/pull/609)).
- Increase our max content length for any interactions with ChatGPT (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#617](https://github.com/10up/classifai/pull/617)).

### Fixed
- Ensure that when using the manual classification mode, all terms will be considered, not just the first 100 (props [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter) via [#638](https://github.com/10up/classifai/pull/638)).
- Ensure that the ClassifAI panel only appears when the related feature is enabled (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#628](https://github.com/10up/classifai/pull/628)).
- More accurate token counts when trimming content (props [@dkotter](https://github.com/dkotter), [@faisal-alvi](https://github.com/faisal-alvi) via [#616](https://github.com/10up/classifai/pull/616)).
- Ensure that updating the "Recommended Content Block" settings works correctly (props [@iamdharmesh](https://github.com/iamdharmesh), [@faisal-alvi](https://github.com/faisal-alvi), [@dkotter](https://github.com/dkotter) via [#625](https://github.com/10up/classifai/pull/625)).

### Security
- Bump `axios` from 0.25.0 to 1.6.2 and `@wordpress/scripts` from 26.6.0 to 26.18.0 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#629](https://github.com/10up/classifai/pull/629)).

## [2.4.0] - 2023-11-09
### Added
- Support for modifying prompts from the admin settings page (props [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@ravinderk](https://github.com/ravinderk), [@dkotter](https://github.com/dkotter) via [#594](https://github.com/10up/classifai/pull/594)).
- Support for setting multiple prompts for each feature that supports prompts (props [@ravinderk](https://github.com/ravinderk), [@iamdharmesh](https://github.com/iamdharmesh), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#602](https://github.com/10up/classifai/pull/602)).
- New filters added to allow developer control over all requests made to OpenAI (props [@faisal-alvi](https://github.com/faisal-alvi), [@jeffpaul](https://github.com/jeffpaul), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#604](https://github.com/10up/classifai/pull/604)).
- Documentation updates in regards to data retention (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#582](https://github.com/10up/classifai/pull/582)).
- Example snippet to make taxonomies private to the developer docs (props [@theskinnyghost](https://github.com/theskinnyghost), [@dkotter](https://github.com/dkotter) via [#583](https://github.com/10up/classifai/pull/583)).
- GitHub Action summary for Cypress E2E checks (props [@faisal-alvi](https://github.com/faisal-alvi), [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#578](https://github.com/10up/classifai/pull/578)).

### Changed
- Ensure the default prompts in ClassifAI show as the first prompt in our settings and cannot be removed or edited (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@ravinderk](https://github.com/ravinderk) via [#610](https://github.com/10up/classifai/pull/610)).
- Fix multiple typos across the codebase (props [@parikshit-adhikari](https://github.com/parikshit-adhikari), [@shresthasurav](https://github.com/shresthasurav), [@jeffpaul](https://github.com/jeffpaul) via [#603](https://github.com/10up/classifai/pull/603), [#605](https://github.com/10up/classifai/pull/605)).
- Use `get_asset_info` across the enqueuing of all our dependencies (props [@ravinderk](https://github.com/ravinderk), [@jeffpaul](https://github.com/jeffpaul), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#608](https://github.com/10up/classifai/pull/608)).
- Bump WordPress "tested up to" version to 6.4 (props [@dkotter](https://github.com/dkotter) via [#613](https://github.com/10up/classifai/pull/613)).

### Fixed
- Ensure all hooks show in our documentation (props [@faisal-alvi](https://github.com/faisal-alvi), [@jeffpaul](https://github.com/jeffpaul), [@berkod](https://github.com/berkod), [@dkotter](https://github.com/dkotter) via [#604](https://github.com/10up/classifai/pull/604)).

### Security
- Bump `@cypress/request` from 2.88.12 to 3.0.0 and `cypress` from 12.14.0 to 13.1.0 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#576](https://github.com/10up/classifai/pull/576)).
- Bump `postcss` from 8.4.24 to 8.4.31 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#584](https://github.com/10up/classifai/pull/584)).
- Bump `@babel/traverse` from 7.22.4 to 7.23.2 (props [@dependabot[bot]](https://github.com/apps/dependabot), [@dkotter](https://github.com/dkotter) via [#601](https://github.com/10up/classifai/pull/601)).

## [2.3.0] - 2023-09-05
**Note that this release bumps the WordPress minimum from 5.7 to 5.8.**

### Added
- Ability to resize (expand or condense) text content using OpenAI's ChatGPT API (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@jakemgold](https://github.com/jakemgold) via [#532](https://github.com/10up/classifai/pull/532)).
- Ability to generate excerpts when using the Classic Editor (props [@jamesmorrison](https://github.com/jamesmorrison), [@ravinderk](https://github.com/ravinderk), [@dkotter](https://github.com/dkotter) via [#491](https://github.com/10up/classifai/pull/491)).
- Ability to generate images directly in the Media Library, instead of at a post level, by going to `Media > Generate Images` (props [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter) via [#524](https://github.com/10up/classifai/pull/524)).
- Ability to generate images within the Inserter Media tab. As of WordPress 6.3, this requires the latest version of the Gutenberg plugin to work. Also note that image generation requests are sent as soon as you are done typing so you may end up making multiple requests as you type out your prompt (resulting in charges for each request), depending on the typing speed (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#535](https://github.com/10up/classifai/pull/535)).
- New display option to control the display of the Text-to-Speech audio controls on the front-end (props [@joshuaabenazer](https://github.com/joshuaabenazer), [@dkotter](https://github.com/dkotter) via [#549](https://github.com/10up/classifai/pull/549)).
- Initial integration with the new Command Palette API (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh), [@ravinderk](https://github.com/ravinderk) via [#536](https://github.com/10up/classifai/pull/536)).
- New `POST` endpoints for title and excerpt generation (props [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter) via [#525](https://github.com/10up/classifai/pull/525)).
- New filter, `classifai_chatgpt_allowed_roles`, to allow ChatGPT image role settings to be overridden (props [@bjorn2404](https://github.com/bjorn2404), [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter) via [#459](https://github.com/10up/classifai/pull/459)).
- New filter, `classifai_openai_dalle_allowed_image_roles`, to allow DALL·E image role settings to be overridden (props [@bjorn2404](https://github.com/bjorn2404), [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter) via [#459](https://github.com/10up/classifai/pull/459)).
- New filter, `classifai_openai_chatgpt_{$feature}`, to allow granular access control for ChatGPT title and excerpt generation (props [@bjorn2404](https://github.com/bjorn2404), [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter) via [#459](https://github.com/10up/classifai/pull/459)).
- New filter, `classifai_openai_dalle_enable_image_gen`, to allow granular access control for DALL·E image generation (props [@bjorn2404](https://github.com/bjorn2404), [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter) via [#459](https://github.com/10up/classifai/pull/459)).

### Changed
- Bump WordPress minimum from 5.7 to 5.8 (props [@Sidsector9](https://github.com/Sidsector9) via [#532](https://github.com/10up/classifai/pull/532)).
- Bump WordPress "tested up to" version to 6.3 (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#556](https://github.com/10up/classifai/pull/556)).
- Utilize the new `POST` endpoints for title and excerpt generation, ensuring most recent content is always used (props [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter) via [#525](https://github.com/10up/classifai/pull/525)).
- Update the IBM Watson NLU API to the `2022-08-10` version (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#543](https://github.com/10up/classifai/pull/543)).
- Update the prompt we send to OpenAI that is used to generate excerpts to try and ensure the excerpts generated pair well with the title of the content (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#544](https://github.com/10up/classifai/pull/544)).
- Update our title generation prompt to use a `system` message (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#545](https://github.com/10up/classifai/pull/545)).
- Better error handling for environments that don't match our minimum PHP version (props [@rahulsprajapati](https://github.com/rahulsprajapati), [@dkotter](https://github.com/dkotter) via [#546](https://github.com/10up/classifai/pull/546)).
- Modify the audio generation process for the TTS feature. Audio generation is enabled by default but will be disabled automatically once audio has been generated (props [@joshuaabenazer](https://github.com/joshuaabenazer), [@dkotter](https://github.com/dkotter) via [#549](https://github.com/10up/classifai/pull/549)).
- Upgrade the Plugin Update Checker library to from 4.13 to 5.1 (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#555](https://github.com/10up/classifai/pull/555)).
- Update the references of the renamed Computer Vision API to Azure AI Vision (props [@kmgalanakis](https://github.com/kmgalanakis), [@dkotter](https://github.com/dkotter) via [#560](https://github.com/10up/classifai/pull/560)).
- Update the Release GitHub Action workflow files to fix an issue where release archives were not being attached (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#565](https://github.com/10up/classifai/pull/565)).

### Fixed
- Ensure we define a class property before using it to avoid PHP deprecation notices (props [@dkotter](https://github.com/dkotter), [@ankitguptaindia](https://github.com/ankitguptaindia), [@Sidsector9](https://github.com/Sidsector9) via [#548](https://github.com/10up/classifai/pull/548)).
- Prevent Text-to-Speech audio markup leakage into places using excerpts (like archives) (props [@joshuaabenazer](https://github.com/joshuaabenazer), [@dkotter](https://github.com/dkotter) via [#558](https://github.com/10up/classifai/pull/558)).
- Make sure our E2E tests work properly on WordPress 6.3 (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#562](https://github.com/10up/classifai/pull/562)).
- Add a longer delay around image generation in the Media Inserter (props [@Sidsector9](https://github.com/joshuaabenazer), [@dkotter](https://github.com/dkotter) via [#569](https://github.com/10up/classifai/pull/569)).

### Security
- Bump `word-wrap` from 1.2.3 to 1.2.4 (props [@dependabot[bot]](https://github.com/apps/dependabot) via [#542](https://github.com/10up/classifai/pull/542)).
- Bump `tough-cookie` from 2.5.0 to 4.1.3 and `@cypress/request` from 2.88.11 to 2.88.12 (props [@dependabot[bot]](https://github.com/apps/dependabot) via [#563](https://github.com/10up/classifai/pull/563)).

## [2.2.3] - 2023-07-13
### Added
- Support post classification via OpenAI Embeddings in the Classic Editor (props [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter) via [#515](https://github.com/10up/classifai/pull/515)).
- Support Text-to-Speech functionality in the Classic Editor (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#518](https://github.com/10up/classifai/pull/518)).
- Custom `WP-CLI` command, `transcribe_audio`, to generate audio transcriptions in bulk (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#514](https://github.com/10up/classifai/pull/514)).
- Custom `WP-CLI` command, `generate_excerpt`, to generate excerpts in bulk (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#516](https://github.com/10up/classifai/pull/516)).
- Custom `WP-CLI` command, `embeddings`, to classify posts via OpenAI Embeddings in bulk (props [@phpbits](https://github.com/phpbits), [@dkotter](https://github.com/dkotter) via [#521](https://github.com/10up/classifai/pull/521)).
- Ability to generate excerpts in bulk using the `Bulk actions` dropdown (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#523](https://github.com/10up/classifai/pull/523)).
- Ability to generate excerpts on an individual item from the All Posts screen (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#523](https://github.com/10up/classifai/pull/523)).
- New filter, `classifai_pre_render_post_audio_controls`, that provides ability to override Text-to-Speech audio player controls markup (props [@joshuaabenazer](https://github.com/joshuaabenazer), [@dkotter](https://github.com/dkotter) via [#528](https://github.com/10up/classifai/pull/528)).
- Provide sample copy that can be added to a site's Privacy Policy, letting site visitors know AI tools are in use (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#529](https://github.com/10up/classifai/pull/529)).

### Changed
- Add singular labels when a single image is selected for generation (props [@jamesmorrison](https://github.com/jamesmorrison), [@dkotter](https://github.com/dkotter) via [#482](https://github.com/10up/classifai/pull/482)).

### Fixed
- Ensure we don't throw any JS errors in our image generation file (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#522](https://github.com/10up/classifai/pull/522)).
- Update Text-to-Speech helper text (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#519](https://github.com/10up/classifai/pull/519)).

## [2.2.2] - 2023-06-28
### Added
- Support for generating post titles in the Classic Editor using OpenAI's ChatGPT API (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#506](https://github.com/10up/classifai/pull/506)).
- New utility method to retrieve all post statuses (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#507](https://github.com/10up/classifai/pull/507)).

### Changed
- Optimized calls to the Microsoft Azure Text to Speech API (props [@joshuaabenazer](https://github.com/joshuaabenazer), [@ravinderk](https://github.com/ravinderk), [@dkotter](https://github.com/dkotter) via [#487](https://github.com/10up/classifai/pull/487)).
- When the Text to Speech option is toggled off, hide the Text to Speech audio button on the single post level (props [@joshuaabenazer](https://github.com/joshuaabenazer), [@ravinderk](https://github.com/ravinderk), [@dkotter](https://github.com/dkotter) via [#494](https://github.com/10up/classifai/pull/494)).
- Update instructions on setting the proper endpoint URL for Azure Text to Speech (props [@dkotter](https://github.com/dkotter), [@ocean90](https://github.com/ocean90) via [#512](https://github.com/10up/classifai/pull/512)).

### Fixed
- Ensure any edits made to generated titles persist when that title is inserted (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#509](https://github.com/10up/classifai/pull/509)).
- Ensure we show all post statuses in our settings instead of just the core post ones (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#507](https://github.com/10up/classifai/pull/507)).

## [2.2.1] - 2023-06-08
### Added
- Ability to generate images in the Classic Editor (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#471](https://github.com/10up/classifai/pull/471)).
- Ability to trigger Text-to-Speech generation in bulk (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#477](https://github.com/10up/classifai/pull/477)).
- Ability to trigger Text-to-Speech generation on an individual item from the post lists screen (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#477](https://github.com/10up/classifai/pull/477)).
- Custom `WP-CLI` command,`text_to_speech`, that can be used to generate Text-to-Speech data in bulk (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#478](https://github.com/10up/classifai/pull/478)).

### Changed
- Tweak the prompt that is used to generate excerpts (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#468](https://github.com/10up/classifai/pull/468)).
- Update the Dependency Review GitHub Action (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#464](https://github.com/10up/classifai/pull/464)).

### Fixed
- Resolve formatting issues in javascript files (props [@ravinderk](https://github.com/ravinderk), [@dkotter](https://github.com/dkotter) via [#461](https://github.com/10up/classifai/pull/461)).
- Correctly add terms to posts generated by Watson content classifiers (props [@ravinderk](https://github.com/ravinderk), [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter) via [#462](https://github.com/10up/classifai/pull/462)).
- Ensure we properly output data on the Site Health screen without causing errors (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#466](https://github.com/10up/classifai/pull/466)).
- Ensure the prompt we send to DALL·E never exceeds 1000 characters (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#467](https://github.com/10up/classifai/pull/467)).
- Ensure quotes aren't added around generated excerpts (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#468](https://github.com/10up/classifai/pull/468)).
- Remove extra slash from asset URLs (props [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#469](https://github.com/10up/classifai/pull/469)).
- Add proper docblocks to all custom hooks to ensure those show properly in our documentation site (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#470](https://github.com/10up/classifai/pull/470)).

### Security
- Bumped various dependencies (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter), [@ravinderk](https://github.com/ravinderk) via [#476](https://github.com/10up/classifai/pull/476)).
- Bump `atob` from 1.1.3 to 2.1.2 and `svg-react-loader` from 0.4.0 to 0.4.6 (props [@dependabot[bot]](https://github.com/apps/dependabot) via [#481](https://github.com/10up/classifai/pull/481)).

## [2.2.0] - 2023-05-22
### Added
- Convert text content into audio and output a "read-to-me" feature on the front-end to play this audio using Microsoft Azure's Text to Speech API (props [@Sidsector9](https://github.com/Sidsector9), [@iamdharmesh](https://github.com/iamdharmesh), [@ravinderk](https://github.com/ravinderk), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter), [@pixeldevsio](https://github.com/pixeldevsio) via [#403](https://github.com/10up/classifai/pull/403)).
- Classify content into existing taxonomy structure using OpenAI's Embeddings API (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh), [@Sidsector9](https://github.com/Sidsector9), [@jeffpaul](https://github.com/jeffpaul) via [#437](https://github.com/10up/classifai/pull/437)).
- Create transcripts of audio files using OpenAI's Whisper API (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#451](https://github.com/10up/classifai/pull/451)).
- Generate SEO-friendly titles using OpenAI's ChatGPT API (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul) via [#452](https://github.com/10up/classifai/pull/452)).

### Changed
- Standardize on how we determine if a Provider is configured (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh), [@benlk](https://github.com/benlk) via [#455](https://github.com/10up/classifai/pull/455)).

### Fixed
- Avoid extra API requests to the IBM Watson NLU endpoint (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh), [@benlk](https://github.com/benlk) via [#455](https://github.com/10up/classifai/pull/455)).
- Ensure the `{$this->menu_slug}_providers` filter works as expected when used to remove Providers (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh) via [#456](https://github.com/10up/classifai/pull/456)).

## [2.1.0] - 2023-05-02
**Note that this release moves the ClassifAI settings to be nested under Tools instead of it's own menu.**

### Added
- New user experience when onboarding, making it easier to get ClassifAI setup and configured (props [@iamdharmesh](https://github.com/iamdharmesh), [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul), [@ryanwelcher](https://github.com/ryanwelcher), [@mehidi258](https://github.com/mehidi258) via [#411](https://github.com/10up/classifai/pull/411)).
- Add proper attribution to generated images (props [@derweili](https://github.com/derweili), [@dkotter](https://github.com/dkotter) via [#438](https://github.com/10up/classifai/pull/438)).
- Add the image generation prompt as `alt` text to imported generated images (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#441](https://github.com/10up/classifai/pull/441)).

### Fixed
- Address a PHP notice that is thrown when editing a non-image attachment (props [@av3nger](https://github.com/av3nger), [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#425](https://github.com/10up/classifai/pull/425)).
- Ensure generated images are assigned properly to their post (props [@derweili](https://github.com/derweili), [@dkotter](https://github.com/dkotter) via [#438](https://github.com/10up/classifai/pull/438)).
- Remove use of deprecated `FILTER_SANITIZE_STRING` constant (props [@Sidsector9](https://github.com/Sidsector9), [@dkotter](https://github.com/dkotter) via [#442](https://github.com/10up/classifai/pull/442)).
- Ensure proper CSS is always loaded based on the enabled features (props [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#441](https://github.com/10up/classifai/pull/441)).
- Removed a duplicate filter (props [@benlk](https://github.com/benlk), [@dkotter](https://github.com/dkotter) via [#444](https://github.com/10up/classifai/pull/444)).
- Buttons to generate descriptive text and image tags are no longer displayed when those settings are disabled in Microsoft Azure Image Processing settings (props [@benlk](https://github.com/benlk), [@dkotter](https://github.com/dkotter) via [#445](https://github.com/10up/classifai/pull/445)).

### Changed
- Use new `get_asset_info` utility for all of our enqueues (props [@Spoygg](https://github.com/Spoygg), [@dkotter](https://github.com/dkotter) via [#421](https://github.com/10up/classifai/pull/421)).
- Change how we import dependencies in our JS files (props [@Spoygg](https://github.com/Spoygg), [@dkotter](https://github.com/dkotter) via [#421](https://github.com/10up/classifai/pull/421)).
- Tweaks to the image generation UI (props [@mehidi258](https://github.com/mehidi258), [@jeffpaul](https://github.com/jeffpaul), [@dkotter](https://github.com/dkotter) via [#441](https://github.com/10up/classifai/pull/441)).
- When attempting to use Azure to parse a too-large document, the error log message will include the document size and the maximum document size (props [@benlk](https://github.com/benlk), [@dkotter](https://github.com/dkotter) via [#443](https://github.com/10up/classifai/pull/443)).

## [2.0.0] - 2023-04-04
### Added
- Automatic generation of excerpts using OpenAI's ChatGPT API (props [@dkotter](https://github.com/dkotter), [@zamanq](https://github.com/zamanq), [@jeffpaul](https://github.com/jeffpaul), [@iamdharmesh](https://github.com/iamdharmesh), [@ravinderk](https://github.com/ravinderk) via [#405](https://github.com/10up/classifai/pull/405), [#408](https://github.com/10up/classifai/pull/408)).
- Generate images using OpenAI's DALL·E API (props [@dkotter](https://github.com/dkotter), [@iamdharmesh](https://github.com/iamdharmesh), [@jeffpaul](https://github.com/jeffpaul), [@joemcgill](https://github.com/joemcgill) via [#419](https://github.com/10up/classifai/pull/419)).
- Ability to set `alt` text as the image caption and image description (props [@Sidsector9](https://github.com/Sidsector9), [@peterwilsoncc](https://github.com/peterwilsoncc), [@jeffpaul](https://github.com/jeffpaul) via [#374](https://github.com/10up/classifai/pull/374)).
- Support for WordPress auto-updates for sites with a valid ClassifAI registration key (props [@TylerB24890](https://github.com/TylerB24890), [@dkotter](https://github.com/dkotter) via [#400](https://github.com/10up/classifai/pull/400)).
- Composer installation instructions added to the `README` (props [@johnwatkins0](https://github.com/johnwatkins0), [@dkotter](https://github.com/dkotter) via [#395](https://github.com/10up/classifai/pull/395)).

### Fixed
- Implement check to prevent requesting a PDF scan on a document which has a scan already in progress (props [@TylerB24890](https://github.com/TylerB24890), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#401](https://github.com/10up/classifai/pull/401)).
- Ensure our E2E and eslint tests pass (props [@dkotter](https://github.com/dkotter), [@jeffpaul](https://github.com/jeffpaul) via [#406](https://github.com/10up/classifai/pull/406), [#407](https://github.com/10up/classifai/pull/407)).
- Removed some unnecessary code in the preview feature (props [@dkotter](https://github.com/dkotter), [@Sidsector9](https://github.com/Sidsector9) via [#402](https://github.com/10up/classifai/pull/402)).
- Remove unnecessary caching in our lint action (props [@szepeviktor](https://github.com/szepeviktor), [@dkotter](https://github.com/dkotter) via [#409](https://github.com/10up/classifai/pull/409)).

### Changed
- Update usage of `get_plugin_settings` to new function signature (props [@Spoygg](https://github.com/Spoygg), [@dkotter](https://github.com/dkotter) via [#418](https://github.com/10up/classifai/pull/418)).
- Cypress integration migrated to 11+ (props [@jayedul](https://github.com/jayedul), [@cadic](https://github.com/cadic) via [#385](https://github.com/10up/classifai/pull/385)).
- Bump WordPress "tested up to" version to 6.2 (props [@ggutenberg](https://github.com/ggutenberg), [@ravinderk](https://github.com/ravinderk) via [#420](https://github.com/10up/classifai/pull/420)).

### Security
- Ensure custom REST endpoints have proper user permission checks (props [@dkotter](https://github.com/dkotter), [@peterwilsoncc](https://github.com/peterwilsoncc) via [GHSA-fxxq-j2vx-j99r](https://github.com/10up/classifai/security/advisories/GHSA-fxxq-j2vx-j99r)).
- Bump `http-cache-semantics` from 4.1.0 to 4.1.1 (props [@dependabot[bot]](https://github.com/apps/dependabot) via [#393](https://github.com/10up/classifai/pull/393)).
- Bump `webpack` from 5.75.0 to 5.76.0 (props [@dependabot[bot]](https://github.com/apps/dependabot) via [#410](https://github.com/10up/classifai/pull/410)).

## [1.8.1] - 2023-01-05
**Note that this release bumps the WordPress minimum from 5.6 to 5.7 and the PHP minimum from 7.2 to 7.4.**

### Added
- New "Build release zip" workflow (props [@iamdharmesh](https://github.com/iamdharmesh), [@peterwilsoncc](https://github.com/peterwilsoncc) via [#390](https://github.com/10up/classifai/pull/390)).

### Changed
- Bump WordPress minimum from 5.6 to 5.7 (props [@zamanq](https://github.com/zamanq), [@Sidsector9](https://github.com/Sidsector9) via [#376](https://github.com/10up/classifai/pull/376)).
- Bump PHP minimum from 7.2 to 7.4 (props [@zamanq](https://github.com/zamanq), [@Sidsector9](https://github.com/Sidsector9) via [#376](https://github.com/10up/classifai/pull/376)).
- Bump WordPress "tested up to" version to 6.1 (props [@iamdharmesh](https://github.com/iamdharmesh), [@cadic](https://github.com/cadic) via [#381](https://github.com/10up/classifai/pull/381)).

### Security
- Bump `decode-uri-component` from 0.2.0 to 0.2.2 (props [@dependabot[bot]](https://github.com/apps/dependabot) via [#383](https://github.com/10up/classifai/pull/383)).
- Bump `simple-git` from 3.10.0 to 3.15.1 (props [@dependabot[bot]](https://github.com/apps/dependabot) via [#384](https://github.com/10up/classifai/pull/384)).

## [1.8.0] - 2022-09-30
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
[3.2.0]: https://github.com/10up/classifai/compare/3.1.1...3.2.0
[3.1.1]: https://github.com/10up/classifai/compare/3.1.0...3.1.1
[3.1.0]: https://github.com/10up/classifai/compare/3.0.1...3.1.0
[3.0.1]: https://github.com/10up/classifai/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/10up/classifai/compare/2.5.1...3.0.0
[2.5.1]: https://github.com/10up/classifai/compare/2.5.0...2.5.1
[2.5.0]: https://github.com/10up/classifai/compare/2.4.0...2.5.0
[2.4.0]: https://github.com/10up/classifai/compare/2.3.0...2.4.0
[2.3.0]: https://github.com/10up/classifai/compare/2.2.3...2.3.0
[2.2.3]: https://github.com/10up/classifai/compare/2.2.2...2.2.3
[2.2.2]: https://github.com/10up/classifai/compare/2.2.1...2.2.2
[2.2.1]: https://github.com/10up/classifai/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/10up/classifai/compare/2.1.0...2.2.0
[2.1.0]: https://github.com/10up/classifai/compare/2.0.0...2.1.0
[2.0.0]: https://github.com/10up/classifai/compare/1.8.1...2.0.0
[1.8.1]: https://github.com/10up/classifai/compare/1.8.0...1.8.1
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
