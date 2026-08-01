# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]
### Added
- Implement `ISearchableGroupBackend`, so the server no longer has to resolve every
  member of the virtual group one uid at a time.
- Implement `INamedBackend`, so group management shows "Everyone" instead of the
  raw backend class name.
- Implement `ICountDisabledInGroup` and `IGetDisplayNameBackend`.

### Fixed
- Group searches no longer return the virtual group for every search term. The
  search term is matched against the group id as well as the translated display
  name, like the database backend does.
- The user count of the virtual group now honours the search term instead of
  always reporting the total number of users.
- Creating a user no longer fails with a `TypeError` when the virtual group cannot
  be resolved.
- The test suite runs again: it required PHPUnit 9, which can no longer load the
  server test case of the supported Nextcloud versions.
- `composer update` works again: the pinned `nextcloud/ocp` dev-master requires
  PHP 8.3, which conflicts with the declared platform and supported server versions.
- Release archives no longer ship the test suite and dev tooling.

### Changed
- Replaced the deprecated `IUserManager::search()` with `searchDisplayName()` and
  `array_sum(countUsers())` with `countUsersTotal()`.
- Bumped PHPUnit to 10.5, nextcloud/coding-standard to 1.5 and pinned
  `nextcloud/ocp` to the minimum supported server version.
- Updated changelog style, added missing releases and release dates.
  [#28](https://github.com/icewind1991/group_everyone/pull/28) @SimJoSt

## [0.1.13] - 2023-06-07
### Added
- Compatible with Nextcloud 27.

## [0.1.12] - 2023-03-10
### Added
- Compatible with Nextcloud 26.

## [0.1.11] - 2022-10-26
### Added
- Compatible with Nextcloud 25.

## [0.1.10] - 2022-04-14
### Added
- Compatible with Nextcloud 24.

## [0.1.9] - 2022-02-15
### Added
- Compatible with Nextcloud 23.

## [0.1.8] - 2021-07-05
### Added
- Compatible with Nextcloud 22.

## [0.1.7] - 2021-02-26
### Added
- Compatible with Nextcloud 21.

## [0.1.6] - 2020-09-22
### Added
- Compatible with Nextcloud 20.

## [0.1.5] - 2020-04-24
### Added
- Add support for the upcoming Nextcloud 19 release.
### Fixed
- Fix existing shares to "everyone" not showing up for newly created users.

## [0.1.4] - 2020-01-15
### Added
- Compatible with Nextcloud 18.

## [0.1.3] - 2019-07-30
### Added
- Compatible with Nextcloud 17.
### Fixed
- Fix calendar sharing when using mysql.
  [#3](https://github.com/icewind1991/group_everyone/pull/3) @fwolfst

## [0.1.2] - 2019-04-02
### Added
- Compatible with Nextcloud 16.

## [0.1.1] - 2019-01-29
### Added
- Mark as compatible with Nextcloud 15.

## [0.1.0] - 2018-08-10
### Added
- Initial release.
