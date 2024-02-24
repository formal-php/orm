# Adapters

By default this ORM comes with 3 `Adapter`s: Filesystem, SQL and Elasticsearch.

## Filesystem

This adapter works with any object that implements `Innmind\Filesystem\Adapter`. This means you can:
- use an in-memory filesystem for your tests
- use a concrete filesystem to prototype a project in its early phase
- use an S3 filesystem if you want to deploy a prototype via AWS's serverless lambdas

You should **not** use this kind of adapter in production.

## SQL

This adapter works with any object that implements `Formal\AccessLayer\Connection`. If you use a connection provided by [`innmind/operating-system`](https://packagist.org/packages/innmind/operating-system) then you can use any SQL database supported by `\PDO`.

This is the kind of adapter you want to use in production.

## Elasticsearch

This adapter works with any object that implements `Innmind\HttpTransport\Transport`.

This is the kind of adapter you want to use to denormalize your aggregates to improve the search speed.
