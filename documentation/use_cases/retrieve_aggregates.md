# Retrieving multiple aggregates

All the operations you'll see in this section are done lazily, this means that when run only one aggregate exist in memory at a time.

## Retrive all aggregates

```php
$_ = $manager
    ->repository(User::class)
    ->all()
    ->foreach(static fn($user) => doStuff($user));
```

This example will call `doStuff()` with every user that is stored.

And since the aggregates are streamed you can safely run this code with thousands of them without risking a memory leak.

## Building a pagination system

```php
$users = $manager
    ->repository(User::class)
    ->all()
    ->drop(100)
    ->take(50)
    ->sequence();
```

This example retrieves the third page of your users and if you want to retrieve the fourth one replace `->drop(100)` by `->drop(150)`.

## Filter the aggregates you need to fetch

To do this you first need to build a specification to represent the filter you want to apply. For this example we'll search for all users with the username `alice` or `bob`.

```php
/**
 * @psalm-immutable
 */
final class Username implements Comparator
{
    use Composable;

    private function __construct(private string $username)
    {
    }

    public static function of(string $username): self
    {
        return new self($username);
    }

    public function property(): string
    {
        // This is the property name on the aggregate
        return 'username';
    }

    public function sign(): Sign
    {
        return Sign::equality;
    }

    public function value()
    {
        // This value must be of the exact same type as the property defined
        // in the aggregate
        return $this->username;
    }
}
```

And then to filter you would do:

```php
$users = $manager
    ->repository(User::class)
    ->matching(Username::of('alice')->or(Username::of('bob')))
    ->sequence();
```

`$repository->matching()` returns the same kind of object as `$repository->all()` meaning you can as easily build a pagination system on filtered data.

You can filter on an aggregate:
- property by using its name as the specification property
- entity property by using `Formal\ORM\Specification\Entity`, the properties don't need to be prefixed by the entity name
- collection property by using `Formal\ORM\Specification\Child`, the properties don't need to be prefixed by the collection name

When filtering on collections an aggregate will be returned as long as at least one child of the collection matches the specification.

!!! warning ""
    You can't filter on optional properties as it may rely on implicit behaviours (ie: checking if a property in an optional entity is null).


## Counting the number of aggregates inside a repository

Sometimes you may just want to know how aggregates you have instead of retrieving them. To do this instead of retrieving them with the examples from above and counting them in memory you can ask the storage to count it for you.

```php
$repository = $manager->repository(User::class);
$all = $repository->size();
$alices = $repository->size(Username::of('alice'));
```

Here we count the total number of users and the number of users with the username `alice`.

The repository also have an `->any()` method to check if there is at least one aggregate and `->none()` to make sure no aggregate exist, and you can pass a specification to both as well.
