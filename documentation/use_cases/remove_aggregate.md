# Removing an aggregate

In order to remove an aggregate you simply need to know its id.

```php
$either = $manager->transactional(
    static fn() => Either::right(
        $manager
            ->repository(User::class)
            ->remove(Id::of(User::class, 'user-uuid')),
    ),
);
```

In the case the id you want to remove doesn't exist it will do nothing.

## Remove multiple aggregates at once

You can remove multiple aggregates at once via a `Specification`. You can use the same specifications as the once to [fetch aggregates](retrieve_aggregates.md#filter-the-aggregates-you-need-to-fetch).

```php
$manager->transactional(
    static fn() => Either::right(
        $manager
            ->repository(User::class)
            ->remove(Username::of('alice')->or(Username::of('bob'))),
    ),
);
```
