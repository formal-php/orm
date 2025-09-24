# Matching across aggregates

You can match aggregates of some kind based on conditions from another aggregate kind.

For example if you have 2 [aggregates](../terminology.md#aggregate) `User` and `Movie` and you want:

> All movies where the director's last name is Blomkamp.

You can write the following code:

```php
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};

$orm
    ->repository(Movie::class)
    ->matching(Property::of(
        'director',
        Sign::in,
        $orm
            ->repository(User::class)
            ->matching(Property::of(
                'lastName',
                Sign::equality,
                'Blomkamp',
            )),
    ))
    ->foreach(static fn($movie) => doSomething($movie));
```

For this to work the `director` property must be typed `Formal\ORM\Id<User>`.

If your storage [adapter](../adapters/index.md) supports it this is even optimised at the storage level.

??? warning
    Only [SQL](../adapters/sql.md) is able to optimize this at the storage level.

    It still works with [Elasticsearch](../adapters/elasticsearch.md) and [Filesystem](../adapters/filesystem.md) but Formal will fetch the matching ids in memory and use them as input value.

## Inversed cross aggregate match

!!! example "Experimental"
    This feature is still experimental and may change in future minor versions.

> All users who directed a movie named Batman

```php
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};

$orm
    ->repository(User::class)
    ->matching(Property::of(
        'id',
        Sign::in,
        $orm
            ->repository(Movie::class)
            ->matching(Property::of(
                'name',
                Sign::contains,
                'Batman',
            ))
            ->property('director'),
    ))
    ->foreach(static fn($user) => doSomething($user));
```

??? tip
    This is an optimized version of:

    ```php
    use Formal\ORM\Id;
    use Innmind\Specification\{
        Comparator\Property,
        Sign,
    };

    $orm
        ->repository(User::class)
        ->matching(Property::of(
            'id',
            Sign::in,
            $orm
                ->repository(Movie::class)
                ->matching(Property::of(
                    'name',
                    Sign::contains,
                    'Batman',
                ))
                ->sequence()
                ->map(static fn($movie): Id => $movie->director()),
        ))
        ->foreach(static fn($user) => doSomething($user));
    ```

    This loads every director `Id` in memory before sending it back to the new query. While the optimized version applies it directly at the adapter level.

??? warning
    The same warning as above applies on this feature.
