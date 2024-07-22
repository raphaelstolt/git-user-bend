# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [v1.5.0] - 2024-07-22

### Added

- The `whoami` command displays the current Git branch.
- New `reset` command.

## [v1.4.0] - 2024-07-19

### Added

- Added the `--branch|-b` option to the  `pair` command. Closes [#14](https://github.com/raphaelstolt/git-user-bend/issues/14).

## [v1.3.1] - 2024-07-18

### Fixed

- Fixed a bug where the `unpair` command didn't reset the former user/persona correctly.

## [v1.3.0] - 2024-07-17

### Added

- New `unpair` command.

## [v1.2.2] - 2024-07-12

### Added

- Attested dist builds.

## [v1.2.1] - 2023-11-28

### Added

- Distribution via PHAR.

## [v1.2.0] - 2023-11-28

### Removed

- Removed support for PHP `8.0`.

## [v1.1.2] - 2017-05-10
### Fixed
- Let `git config` collect the persona details from global > local > conditional includes. Fixes [#5](https://github.com/raphaelstolt/git-user-bend/issues/5).

## [v1.1.1] - 2017-05-02
### Fixed
- Exclude release related utilities from release.

## [v1.1.0] - 2017-05-02
### Added
- Additional `--edit|-e` option on `personas` command to edit persona details. Closes [#4](https://github.com/raphaelstolt/git-user-bend/issues/4).

## [v1.0.4] - 2017-04-08
### Fixed
- Fix dotfile spelling. Fixes [#2](https://github.com/raphaelstolt/git-user-bend/issues/2).

## [v1.0.3] - 2017-04-04
### Fixed
- Fix pair detection. Fixes [#3](https://github.com/raphaelstolt/git-user-bend/issues/3).

## [v1.0.2] - 2017-04-04
### Added
- Additional guard against dual alias and aliases argument usage on `use` command.

### Fixed
- Fix usage of single persona alias. Fixes [#1](https://github.com/raphaelstolt/git-user-bend/issues/1).

## [v1.0.1] - 2017-04-03
### Fixed
- Some minor bug fixes.

## v1.0.0 - 2017-04-03
- First release.

[Unreleased]: https://github.com/raphaelstolt/git-user-bend/compare/v1.5.0...HEAD
[v1.5.0]: https://github.com/raphaelstolt/git-user-bend/compare/v1.4.0...v1.5.0
[v1.4.0]: https://github.com/raphaelstolt/git-user-bend/compare/v1.3.1...v1.4.0
[v1.3.1]: https://github.com/raphaelstolt/git-user-bend/compare/v1.3.0...v1.3.1
[v1.3.0]: https://github.com/raphaelstolt/git-user-bend/compare/v1.2.2...v1.3.0
[v1.2.2]: https://github.com/raphaelstolt/git-user-bend/compare/v1.2.1...v1.2.2
[v1.2.1]: https://github.com/raphaelstolt/git-user-bend/compare/v1.2.0...v1.2.1
[v1.2.0]: https://github.com/raphaelstolt/git-user-bend/compare/v1.1.2...v1.2.0
[v1.1.2]: https://github.com/raphaelstolt/git-user-bend/compare/v1.1.1...v1.1.2
[v1.1.1]: https://github.com/raphaelstolt/git-user-bend/compare/v1.1.0...v1.1.1
[v1.1.0]: https://github.com/raphaelstolt/git-user-bend/compare/v1.0.4...v1.1.0
[v1.0.4]: https://github.com/raphaelstolt/git-user-bend/compare/v1.0.3...v1.0.4
[v1.0.3]: https://github.com/raphaelstolt/git-user-bend/compare/v1.0.2...v1.0.3
[v1.0.2]: https://github.com/raphaelstolt/git-user-bend/compare/v1.0.1...v1.0.2
[v1.0.1]: https://github.com/raphaelstolt/git-user-bend/compare/v1.0.0...v1.0.1
