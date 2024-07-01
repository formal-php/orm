# Persist an aggregate

In order to persist an aggregate you need 3 things:

- an instance of the [aggregate](../terminology.md#aggregate)
- a [repository](../terimonology.md#repository) in which to put the aggregate
- a [transaction](../terminology.md#transaction) to atomically persist the aggregate

Translated into code this gives:

```php
use Innmind\Immutable\Either;

$user = User::new('alice');
$users = $orm->repository(User::class);
$result = $orm->transactional(
    static function() use ($repository, $user) {
        $repository->put($user);

        return Either::right(null);
    };
);
```

Once again not much code but a lot to learn.

The `$user` aggregate here is instanciated outside the transaction. It's simple PHP that is not aware of the ORM and the transaction, this means you can instanciate your aggregates anywhere you want in your app.

The `$users` repository is the abstraction that _represent all users_ in the storage. It's when you call the `$orm->repository()` method that the ORM will scan the aggregate class to learn how to persist it.

!!! success ""
    You **should** only call this method once per aggregate type and then keep it in memory, as this scan will slow your app if you do it many times. However if your app is in a long living process and you want your memory footprint to be as low as possible you may want to dereference the repository and rebuild it upon use.

    Use your best judgment to choose the best option for your need.

`$orm->transactional()` will start a transaction via the storage used by the ORM. It will then call the callable you passed to it. If the returned value is an `Either` with a value on the right side it will commit the transaction and return the `Either` object as the `$result` variable. If the `Either` contains a value on the left side it will rollback the transaction and return the `Either` object as the `$result` variable. If the callable throws an exception it will rollback as well an rethrow the exception.

`$repository->put()` will call the storage to persist the aggregate. At this point the ORM knows to create the aggregate because it's unaware before that of its `Id` object reference. After the `put` the ORM is now aware of this id and if you do another `put` it will try to update the same entry in the storage. (1)
{ .annotate }

1. That's why you can't clone an `Id` object as the ORM would no longer know if it needs to insert or update the aggregate.

Finally we return `Either::right(null)` to tell the ORM to commit the transaction. We use `null` because there's no business logic done here. But you could imagine returning a computed value instead, or return a business error object on the left side of the `Either`.

This example only persist one aggregate but you can persist as many as you want.

!!! warning ""
    If you try to call `put` outside of a transaction it will throw an exception. This is a design choice to prevent an accidental modification to your storage outside of an expected transaction.

    This forces an application design to be more explicit in where a modification can happen.

Also the `put` here is done directly inside the callable, but it can happen anywhere in a call stack that originated from this callable.
