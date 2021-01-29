# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.1] - 2021-01-29

- Fixed parsing dictionary items which values contain colons

## [2.1.0] - 2021-01-23

### Fixed

- Fixed issue with 2x recursive lists

### Changed

- Rewrote parsing engine

## [2.0.0] - 2021-01-04

### Changed

- Changed minimum PHP version to 7.3
- Change minimum PHPUnit to 9.2

## [1.2.0] - 2020-07-29

### Fixed

- Fixed parsing data type from lists
- Fixed multi-line parsing and dumping

### Added

- Added parsing of Null,NULL,~
- Added parsing of False,FALSE
- Added parsing of True,TRUE

### Changed

- Changed empty arrays are now dumped as []
- Changed Parses integers returns value as int type
- Changed Parses floats returns value as float type

## [1.1.3] - 2020-07-26

### Fixed

- Fixed (improved) parsing/dumping of empty values, arrays, nulls and strings

## [1.1.2] - 2020-07-26

### Fixed

- Fixed issue with parsing parent e.g. `data:` which have trailing spaces

### Changed

- Changed dump to not include trailing space after parent

## [1.1.1] - 2020-05-26

### Changed

- Multiline strings no longer trimmed, to keep formatting, multiline strings expect 2 spaces after parent e.g

```yaml
parent: |
  the quick brown
```

### Fixed
- Fixed continuous looping if last line is parent with no value
- Fixed converting multi line strings which had YAML, which was then parsed

## [1.0.0] - 2019-10-12

This component has been decoupled from the [OriginPHP framework](https://www.originphp.com/).