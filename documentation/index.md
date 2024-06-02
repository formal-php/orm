---
hide:
    - navigation
    - toc
---

# Getting started

This ORM allows you to store your objects via different adapters with almost no configuration.

## Installation

```sh
composer require formal/orm
```

## Usage example

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
