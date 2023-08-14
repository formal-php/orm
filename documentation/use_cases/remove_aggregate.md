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
