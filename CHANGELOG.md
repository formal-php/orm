# Changelog

## 5.0.0 - 2025-06-04

### Changed

- Requires `innmind/foundation:~1.1`
- `Formal\ORM\Definition\Type\PointInTimeType\Format` has been renamed `Formats` and is now an enum
- `Formal\ORM\Repository::effect()` now returns an `Innmind\Immutable\Attempt<Innmind\Immutable\SideEffect>`
- `Formal\ORM\Repository::put()` now returns an `Innmind\Immutable\Attempt<Innmind\Immutable\SideEffect>`
- `Formal\ORM\Repository::remove()` now returns an `Innmind\Immutable\Attempt<Innmind\Immutable\SideEffect>`
- `Formal\ORM\Adapter\Transaction` methods now uses `Innmind\Immutable\Attempt` to handle errors
- `Formal\ORM\Manager::transactional()` may also return a `Formal\ORM\Adapter\Transaction\Failure` as a left value

### Removed

- `Formal\ORM\Definition\Type\PointInTimeType::of()`

### Fixed

- Float properties couldn't not be loaded from Elasticsearch

## 4.1.1 - 2025-05-02

### Fixed

- Collections of entities weren't stored correctly by the SQL adapter

## 4.1.0 - 2025-04-30

### Added

- `Formal\ORM\Repository::effect()`
- `Formal\ORM\Effect`
- `Formal\ORM\Definition\Contains\Primitive` to be used on a `Maybe` property

### Changed

- The following types are now enums with a single case to avoid creating too many objects in large projects
    - `Formal\ORM\Definition\Type\BoolType`
    - `Formal\ORM\Definition\Type\IdType`
    - `Formal\ORM\Definition\Type\IntType`
    - `Formal\ORM\Definition\Type\StringType`
    - `Formal\ORM\Definition\Type\StrType`

## 4.0.0 - 2024-10-31

### Added

- Allow to convert types to floating points.

### Fixed

- Cross matching on an aggregate or entity property.

## 3.4.1 - 2024-10-26

### Changed

- Use `static` closures as much as possible to reduce the probability of creating circular references by capturing `$this` as it can lead to memory root buffer exhaustion.

## 3.4.0 - 2024-10-02

### Added

- `Formal\ORM\Adapter\SQL\ShowCreateTable::ifNotExists()`

## 3.3.0 - 2024-09-29

### Added

- Ability to use the comparison `in Matching` in a specification. This allows to build complex queries across different aggregates.

### Changed

- SQL columns storing `Formal\ORM\Id`s now use the `uuid` type. To use the _Cross Aggregate Matching_ feature with PostgreSQL you must migrate your schema.

## 3.2.0 - 2024-08-20

### Added

- `Formal\ORM\Specification\Has`

### Fixed

- Updating an optional entity resulting in no property change no longer raised an exception when stored via SQL nor it generates an invalid document in Elasticsearch

## 3.1.1 - 2024-08-01

### Added

- `Formal\ORM\Definition\Type\PointInTimeType::new()`

### Deprecated

- `Formal\ORM\Definition\Type\PointInTimeType::of()` as it uses a non standard string format. Use `::new()` instead, but don't forget to migrate your data.

### Fixed

- Psalm was complaining of a missing argument when using `PointInTimeType::of()`

## 3.1.0 - 2024-07-26

### Added

- `Formal\ORM\Definition\Aggregagtes::mapName()`

## 3.0.0 - 2024-07-14

### Added

- `Formal\ORM\Adapter\Repository::removeAll()`
- `Formal\ORM\Specification\Child\Enum`

### Changed

- Requires `innmind/specification:~4.0`
- Requires `formal/access-layer:~4.0`

### Removed

- `Formal\ORM\Adapter\Repository\MassRemoval`, its method has been merged into `Formal\ORM\Adapter\Repository`

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
