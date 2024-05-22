# Changelog

## 2.0.1 - 2024-05-22

### Fixed

- Properties named `id` are now parsed correctly in entities

## 2.0.0 - 2024-02-25

### Added

- You can match aggregates on collections via `Formal\ORM\Specification\Child`
- `Formal\ORM\Adapter\Repository::any()`
- You can use any enum as a property type (nullable/optional or not)
- You can use any enum inside `Set`s without having to wrap them in another class
- `Formal\ORM\Adapter\Elasticsearch` to store aggregates in Elasticsearch
- `Formal\ORM\Adapter\Elasticsearch\CreateIndex`
- `Formal\ORM\Adapter\Elasticsearch\DropIndex`

### Changed

- Aggregates are now stored on multiple files with the `Filesystem` adapter
- (Optional) Entities id column with the `SQL` adapter now use the Aggregate id as a value, the columns in the Aggregate column referencing these columns have been removed
- `Formal\ORM\Raw\Aggregate\Collection::properties()` has been renamed `::entities()`
- Collection, entities and optional entities table column `id` has been renamed `aggregateId`
- `Formal\ORM\Specification\Entity` now has a similar api to `Child`

## 1.2.0 - 2024-01-15

### Changed

- Requires `innmind/filesystem:~7.4`

## 1.1.0 - 2023-09-23

### Added

- Support for `innmind/immutable:~5.0`
