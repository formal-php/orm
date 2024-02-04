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
use Formal\ORM\{
    Manager,
    Sort,
};
use Formal\AccessLayer\Connection\PDO;
use Innmind\Url\Url;

$manager = Manager::sql(
    PDO::of(Url::of('mysql://user:pwd@host:3306/database?charset=utf8mb4')),
);
$_ = $manager
    ->repository(YourAggregate::class)
    ->all()
    ->sort('someProperty', Sort::asc)
    ->drop(150)
    ->take(50)
    ->foreach(static fn($aggregate) => doStuff($aggregate));
```

This simple example will retrieve from the database `50` elements (from index `151` to `200`) sorted by `someProperty` in ascending order and will call the function `doStuff` on each aggregate.

> [!NOTE]
> The elements are streamed meaning only one aggregate is in memory at a time allowing you to deal with long lists of elements in a memory safe way.

## Documentation

Full documentation available in the [documentation folder](documentation/).

## Benchmark

A small benchmark as a reference point for the performance of this ORM consists in generating and persisting 100K users in a single transaction and then loading them.

```sh
time php benchmark/fill_storage.php
php benchmark/fill_storage.php  222.24s user 5.20s system 60% cpu 6:18.40 total
time php benchmark/load.php
Memory: 40.00 Mo
php benchmark/load.php  11.06s user 0.08s system 97% cpu 11.388 total
```

This means the ORM can load 1 aggregate in 0.1 millisecond.

This was run on a MacbookPro 16" with a M1 Max with the mariadb running inside Docker.

**Note**: If all the aggregates were to be stored in memory it would take around 2Go of RAM and 15 seconds to complete.
