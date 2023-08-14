# ORM

[![Build Status](https://github.com/formal-php/orm/workflows/CI/badge.svg?branch=master)](https://github.com/formal-php/orm/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/formal-php/orm/branch/develop/graph/badge.svg)](https://codecov.io/gh/formal-php/orm)
[![Type Coverage](https://shepherd.dev/github/formal-php/orm/coverage.svg)](https://shepherd.dev/github/formal-php/orm)

This ORM (Object-Relational Mapping) is focused to work in long living processes and being able to work in an asynchronous context.

This is achieved by:
- being memory efficient (objects are not kept in memory)
- relying on the [Innmind](https://github.com/Innmind) platform
- using immutable objects

## Installation

```sh
composer require formal/orm
```

## Usage

```php
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;
use Formal\ORM\{
    Manager,
    Adapter,
    Sort,
    Definition\Aggregates,
    Definition\Types,
    Definition\Type,
};

$os = Factory::build();
$manager = Manager::of(
    Adapter\SQL::lazy(
        static fn() => $os->remote()->sql(Url::of('mysql://user:pwd@host:3306/database?charset=utf8mb4')),
    ),
    Aggregates::of(Types::of(
        Type\PointInTimeType::of($os->clock()),
    )),
);
$_ = $manager
    ->repository(YourAggregate::class)
    ->all()
    ->sort('someProperty', Sort::asc)
    ->drop(150)
    ->take(50)
    ->fetch()
    ->foreach(static fn($aggregate) => doStuff($aggregate));
```

This simple example will retrieve from the database `50` elements (from index `151` to `200`) sorted by `someProperty` in ascending order and will call the function `doStuff` on each aggregate.

**Note**: The elements are streamed meaning only one aggregate is in memory at a time allowing you to deal with long lists of elements in a memory safe way.

**Note 2**: Since the aggregates are streamed this also means that iterating a second time on the `Sequence` returned by `fetch()` will re-call your storage.

**Note 3**: This example uses [`innmind/operating-system`](https://packagist.org/packages/innmind/operating-system) but is not directly required by this package.

## Documentation

Full documentation available in the [documentation folder](documentation/).
