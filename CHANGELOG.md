# Changelog

## [Unreleased]

### Added

- You can match aggregates on collections via `Formal\ORM\Specification\Child`
- `Formal\ORM\Adapter\Repository::any()`

### Changed

- Aggregates are now stored on multiple files with the `Filesystem` adapter
- (Optional) Entities id column with the `SQL` adapter now use the Aggregate id as a value, the columns in the Aggregate column referencing these columns have been removed

## 1.2.0 - 2024-01-15

### Changed

- Requires `innmind/filesystem:~7.4`

## 1.1.0 - 2023-09-23

### Added

- Support for `innmind/immutable:~5.0`
