# Count aggregates

At some point you may need to count the number of aggregates stored. You can do it like this:

```php
$count = $orm->repository(User::class)->size();
```

This will return `0` or more.

And sometime you may need to simply know if there's at least one aggregate or none. For this case you **should not** use the `size` method as it overfetches data. Instead you can do:

```php
$trueWhenTheresAtLeastOneUser = $orm->repository(User::class)->any();
// or
$trueWhenTheresNoUser = $orm->repository(User::class)->none();
```

??? note
    If you want to count the number of aggregates corresponding to a set of criteria head to the [Specification chapter](../specifications/index.md).
