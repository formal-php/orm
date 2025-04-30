---
authors: [baptouuuu]
date: 2025-04-30
---

# Mass update

In the latest release Formal now offers a way to update multiple aggregates directly at the adapter level thanks to a new feature called [`Effect`s](../../effects/index.md)

<!-- more -->

Until now to update multiple aggregates you'd have to fetch all of them, update each one and then put them back in the repository. It would look something like this:

```php
$users = $orm->repository(User::class);
$users
    ->all() #(1)
    ->map(static fn(User $user): User => $user->rename('Alice'))
    ->foreach($users->put(...)); #(2)
```

1. or `->matching()` with some [specification](../../specifications/index.md)
2. the [transaction](../../terminology.md#transaction) is omitted for brievety

The problem with this approach is that it's terribly inefficient. You have 1 query to fetch the aggregates and then 1 query for each user to update.

Instead, you can now use [`Effect`s](../../effects/index.md):

```php
use Formal\ORM\Effect;

$orm
    ->repository(User::class)
    ->effect(
        Effect::property('name')->assign('Alice'),
        #(1)
    ); #(2)
```

1. you can pass an optional [specification](../../specifications/index.md) to update a subset of aggregates
2. the call must be done in a transaction, but is omitted here for brievety

This example will rename all users as `Alice` in a single query.

And this works for all adapters: [Filesystem](../../adapters/filesystem.md), [SQL](../../adapters/sql.md) and [Elasticsearch](../../adapters/elasticsearch.md).

With this new feature you can:

- assign one or multiple properties
    - for the aggregate
    - for entities
    - for optional entities
- remove an optional entity
- add an entity to a collection
- remove entities from a collection

---

For now it's not possible to compose multiple effects together and apply them at once due to a limitation of the SQL adapter.

It would require to execute mutiple queries but the matched aggregates could change between each query if the specification rely on data being modified by the effects.

This would result in an implicit behaviour.

However you can still call the `->effect()` method multiple times within the same transaction.

This way this potential problem is explicit in your code and is not less efficient since it will do the same number of queries.

Here's an example to illustrate this situation:

=== "Implicit"
    ```php
    use Formal\ORM\Effect;
    use Innmind\Specification\{
        Comparator\Property,
        Sign,
    };

    $orm
        ->repository(User::class)
        ->effect(
            Effect::property('name')
                ->assign('Alice')
                ->and( #(1)
                    Effect::entity('status')->properties(
                        Effect::property('enabled')->assign(false),
                    ),
                ),
            Property::of(
                'name',
                Sign::equality,
                'Jane',
            ),
        );
    ```

    1. bear in mind this is not possible

    When reading this example you would expect all `Jane` users to be renamed `Alice` and be disabled.

    However with the current SQL adapter implementation it would result in all `Jane` users to be renamed `Alice` but their status wouldn't be changed. Because it would first do `#!sql UPDATE users SET name = 'Alice' WHERE name = 'Jane'` and then `#!sql UPDATE users_status SET enabled = 0 WHERE aggregateId IN (SELECT id FROM users WHERE name = 'Jane')`. But for the latter there is no longer any user named `Jane`.

=== "Explicit"
    ```php
    use Formal\ORM\Effect;
    use Innmind\Specification\{
        Comparator\Property,
        Sign,
    };

    $orm
        ->repository(User::class)
        ->effect(
            Effect::property('name')->assign('Alice'),
            Property::of(
                'name',
                Sign::equality,
                'Jane',
            ),
        );
    $orm
        ->repository(User::class)
        ->effect(
            Effect::entity('status')->properties(
                Effect::property('enabled')->assign(false),
            ),
            Property::of(
                'name',
                Sign::equality,
                'Jane',
            ),
        );
    ```

    This is the same thing as the previous implicit example. But here it's apparent that we have a design problem since there can't be any user named `Jane` when applying the second effect.

    To fix this, you simply need to switch the 2 effects:

    ```php
    use Formal\ORM\Effect;
    use Innmind\Specification\{
        Comparator\Property,
        Sign,
    };

    $orm
        ->repository(User::class)
        ->effect(
            Effect::entity('status')->properties(
                Effect::property('enabled')->assign(false),
            ),
            Property::of(
                'name',
                Sign::equality,
                'Jane',
            ),
        );
    $orm
        ->repository(User::class)
        ->effect(
            Effect::property('name')->assign('Alice'),
            Property::of(
                'name',
                Sign::equality,
                'Jane',
            ),
        );
    ```

    You still have only 2 queries, and have the expected behaviour.
