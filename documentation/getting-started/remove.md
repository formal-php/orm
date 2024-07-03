# Remove an aggregate

In order to remove an aggregate you need 3 things:

- an `Id` of the [aggregate](../terminology.md#aggregate)
- a [repository](../terminology.md#repository) in which to put the aggregate
- a [transaction](../terminology.md#transaction) to atomically persist the aggregate

Translated into code this gives:

```php
use Innmind\Immutable\Either;

$users = $orm->repository(User::class);
$result = $orm->transactional(
    static function() use ($repository) {
        $repository->remove(Id::of(User::class, 'alice-uuid'));

        return Either::right(null);
    };
);
```

If alice exists in the storage it will remove it and if it doesn't then nothing will happen. And like for [persisting](persist.md) the `Either::right(null)` will indicate to `transactional` to commit the transaction.

??? note
    If you want to remove multiple aggregates at once corresponding to a set of criteria head to the [Specification chapter](../specifications/index.md).
