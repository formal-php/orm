---
hide:
    - navigation
    - toc
---

# Welcome to the Formal ORM

This ORM focuses to simplify data manipulation.

This is achieved by:

- using immutable objects
- each aggregate _owning_ the objects it references
- using monads to fetch aggregates (from the [Innmind](https://innmind.github.io/documentation/getting-started/handling-data/) ecosystem)
- using the specification pattern to match aggregates

This allows:

- simpler app design (as it can be [pure](https://innmind.github.io/documentation/philosophy/oop-fp/#purity))
- memory efficiency (the ORM doesn't keep objects in memory)
- long living processes (since there is no memory leaks)
- to work asynchronously

??? example "Sneak peak"
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

If you've worked with [C# Entity Framework](https://learn.microsoft.com/en-us/ef/core/get-started/overview/first-app) you should find a few similarities.

*[ORM]: Object Relational Mapping
