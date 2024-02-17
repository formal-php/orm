# Changelog

## [Unreleased]

### Added

- You can match aggregates on collections via `Formal\ORM\Specification\Child`
- `Formal\ORM\Adapter\Repository::any()`
- Collections are now diffed when updating an Aggregate to only insert the new entities instead of writing everything each time
    - internally it uses an _entity reference_ but it doesn't impact user classes
- You can use any enum as a property type (nullable/optional or not)
- You can use any enum inside `Set`s without having to wrap them in another class

### Changed

- Aggregates are now stored on multiple files with the `Filesystem` adapter
- (Optional) Entities id column with the `SQL` adapter now use the Aggregate id as a value, the columns in the Aggregate column referencing these columns have been removed
- `Formal\ORM\Raw\Aggregate\Collection::properties()` has been renamed `::entities()`
- Collection tables now have an `entityReference` column
- Collection, entities and optional entities table column `id` has been renamed `aggregateId`

## 1.2.0 - 2024-01-15

### Changed

- Requires `innmind/filesystem:~7.4`

## 1.1.0 - 2023-09-23

### Added

- Support for `innmind/immutable:~5.0`
