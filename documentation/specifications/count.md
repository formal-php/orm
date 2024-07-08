# Count multiple aggregates

You can count aggregates by [retrieving them](retrieve.md) and count the elements in the returned sequence like this:

```php
$numberOfAlices = $orm
    ->repository(User::class)
    ->matching(
        SearchByName::of(Name::of('alice')),
    )
    ->sequence()
    ->size();
```

!!! danger ""
    But you **MUST NOT** do this. This will fetch the aggregates in memory and count them in PHP, this will be extremely slow!

The right approach is:

```php
$numberOfAlices = $orm
    ->repository(User::class)
    ->size(
        SearchByName::of(Name::of('alice')),
    );
```

This will run an optimized count in your storage.

But if you only need to know if there's an aggregate in the storage matching the specification, you **SHOULD** do:

```php hl_lines="3"
$numberOfAlices = $orm
    ->repository(User::class)
    ->any(
        SearchByName::of(Name::of('alice')),
    );
```

This runs an even more optimized query against your storage.

And if you need to make sure no aggregate matches a specification:

```php hl_lines="3"
$numberOfAlices = $orm
    ->repository(User::class)
    ->none(
        SearchByName::of(Name::of('alice')),
    );
```

??? tip
    The specification passed to `any` and `none` is optional, allowing you to know if there at least one aggregate or none (as the name would suggest).
