# Retrieve multiple aggregates

You can do so via the `matching` method on a repository:

```php
$orm
    ->repository(User::class)
    ->matching(
        SearchByName::of(Name::of('alice')),
    )
    ->foreach(static fn(User $alice) => businessLogic($alice));
```

??? tip
    The `foreach` method used here is a shortcut for `->sequence()->foreach()`. This means that you can have access to a [`Sequence`](https://innmind.github.io/documentation/getting-started/handling-data/sequence/) by just calling `->sequence()` and use it like any other `Sequence`.
