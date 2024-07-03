# Retrieve an aggregate

Once an aggregate has been persisted you'll want to load it at some point.

The easiest way to retrieve it is to load all aggregates and filter the one you want:

```php
$alice = $orm
    ->repository(User::class)
    ->all()
    ->find(static fn(User $user) => $user->name() === 'alice')
    ->match(
        static fn(User $user) => $user,
        static fn() => null,
    );
```

If there's an `alice` user in the storage then `$alice` will be an instance of `User` otherwise it will be `null`.

??? note
    Note that you don't need to be in a transaction to fetch your aggregates.

While this example is simple enough it's not very performant as it loads every aggregate from the storage until it finds alice. The better approach is to directly fetch alice via its id:

```php
$alice = $orm
    ->repository(User::class)
    ->get(Id::of(User::class, 'alice-uuid'))
    ->match(
        static fn(User $user) => $user,
        static fn() => null,
    );
```

Here we use `alice-uuid` as the id value but this is a placeholder. You should replace it with the real id value, usually it will come from a HTTP route parameter.

??? info
    An `Id` can be transformed to a string via the `$id->toString()` method.

The `get` method returns a `Maybe<User>` as the corresponding user may not exist in the storage. Here we return `null` if alice doesn't exist but you can return or call any code you'd like.

??? note
    If you want to learn how to retrieve mutliple aggregates corresponding to a set of criteria head to the [Specification chapter](../specifications/index.md).

??? note
    Note that the monads are lazy evaluated when retrieving data. This means that it will hit the storage only when trying to extract data from them and will only load one aggregate at a time.

    For a `Maybe` this means calling `match` or `memoize`. For a `Sequence` it's all methods marked with :material-memory-arrow-down: in [its documentation](http://innmind.github.io/Immutable/structures/sequence/).
