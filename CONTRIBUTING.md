# Contributing and Maintaining

First, thank you for taking the time to contribute!

The following is a set of guidelines for contributors as well as information and instructions around our maintenance process. The two are closely tied together in terms of how we all work together and set expectations, so while you may not need to know everything in here to submit an issue or pull request, it's best to keep them in the same document.

## Ways to contribute

Contributing isn't just writing code - it's anything that improves the project. All contributions for ClassifAI are managed right here on GitHub. Here are some ways you can help:

### Reporting bugs

If you're running into an issue with the plugin, please take a look through [existing issues](https://github.com/10up/classifai/issues) and [open a new one](https://github.com/10up/classifai/issues/new) if needed. If you're able, include steps to reproduce, environment information, and screenshots/screencasts as relevant.

### Suggesting enhancements

New features and enhancements are also managed via [issues](https://github.com/10up/classifai/issues).

### Pull requests

Pull requests represent a proposed solution to a specified problem. They should always reference an issue that describes the problem and contains discussion about the problem itself. Discussion on pull requests should be limited to the pull request itself, i.e. code review.

For more on how 10up writes and manages code, check out our [10up Engineering Best Practices](https://10up.github.io/Engineering-Best-Practices/).

## Workflow

The `develop` branch is the development branch which means it contains the next version to be released. `stable` contains the current latest release and `master` contains the corresponding stable development version. Always work on the `develop` branch and open up PRs against `develop`.

## Release instructions

1. Starting from `develop`, cut a release branch named `release/X.Y.Z` for your changes.
2. Version bump: Bump the version number in `classifai.php` and `config.php` if it does not already reflect the version being released.
3. Changelog: Add/update the changelog in `CHANGELOG.md`
4. Update the `.pot` file by running `npm run makepot`.
5. Check to be sure any new files/paths that are unnecessary in the production version are included in `.github/action-release/rsync-filter.txt`.
6. Merge: Make a non-fast-forward merge from your release branch to `develop`, then do the same for `develop` into `master`. `master` contains the stable development version.
7. Push: Push your master branch to GitHub, e.g. `git push origin master`.
8. [Wait for build](https://xkcd.com/303/): Head to the [Actions](https://github.com/10up/classifai/actions) tab in the repo and wait for it to finish if it hasn't already. If it doesn't succeed, figure out why and start over.
9. Check the build: Check out the `stable` branch and test for functionality locally.
10. Git tag: Create the release as `X.Y.Z` on the `stable` branch in GitHub. It should now appear under [releases](https://github.com/10up/classifai/releases) and in the WordPress admin as an update as well.
