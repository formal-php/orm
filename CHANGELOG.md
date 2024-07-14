# Changelog

## [Unreleased]

### Changed

- Requires `innmind/specification:~4.0`
- Requires `formal/access-layer:~4.0`

### Fixed

- Ability to remove with a condition on an entity property

## 2.2.0 - 2024-07-08

### Added

- `Formal\ORM\Adapter\Repository\MassRemoval`
- You can match aggregates on optionals via `Formal\ORM\Specification\Just`
- `Formal\ORM\Definition\Type\Support`

### Changed

- You can now pass a `Specification` to `Repository::remove()` to remove multiple aggregates at once
- When a `Set` is modified in an aggregate but the resulting `Set` contains the same values the orm no longer re-persist the whole collection
- The `Contains` attribute now enforce to only be used on properties

## 2.1.0 - 2024-06-02

### Added

- `Formal\ORM\Id::for()`

## 2.0.2 - 2024-05-29

### Changed

- Requires `innmind/immutable:~5.4`
- Requires `formal/access-layer:~2.17`

### Fixed

- `false` values not being persisted
- Silent insert failures of entities inside collections

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
