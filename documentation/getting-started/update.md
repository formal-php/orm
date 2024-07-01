# Update an aggregate

Now that you know howto load aggregates from the storage let's say that for some reason you want to rename `alice` to `bob`. The first part is to add a new method on our `User`:

```php title="User.php" hl_lines="23-26"
use Formal\ORM\Id;

final readonly class User
{
    /**
     * @param Id<self> $id
     */
    private function __construct(
        private Id $id,
        private string $name,
    ) {}

    public static function new(string $name): self
    {
        return new self(Id::new(self::class), $name);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rename(string $name): self
    {
        return new self($this->id, $name);
    }
}
```

Since our aggregate is readonly we need to return a new instance. The only difference in the new `User` object is the new name.

??? tip
    The private constructor shines here because it forces a new instance to specify all the previous properties that don't change. This without the public api to know how the internal object behaves, so far nobody outside the class knows there's an `id` property.

    Specifying each property to instanciate a new version of the aggregate can be seen as repetitive and tiring but this is intended to provide a _wake up call_. If you need to write 50(1) properties for each _modifier_ method, then maybe your class tries to do to much and should be refactored or split up.
    {.annotate}

    1. (this is an arbitrary number)

    Also you should not provide default values to the `__construct` parameters, this will help make sure you didn't forget to copy any property (that would result in a state change).

    In the end you may still be tempted to use `clone`. Know that this will work as it doesn't change a thing for the ORM. Yet this practice is frowned upon by this project as it favors [implicits](https://innmind.github.io/documentation/philosophy/explicit/).

And to then apply our change:

```php
use Formal\ORM\Id;
use Innmind\Immutable\Either;

$repository = $orm->repository(User::class);
$orm->transactional(
    static function() use ($repository) {
        $_ = $repository
            ->get(Id::of(User::class, 'alice-uuid'))
            ->map(static fn(User $alice) => $alice->rename('bob'))
            ->match(
                static fn(User $bob) => $repository->put($bob),
                static fn() => null,
            );

        return Either::right(null);
    },
);
```

As seen before we fetch alice via its id, if the object exist then the callable passed to `map` is called. Then in `match` either we have the new `User` object representing bob and persist it again or we do nothing by returning `null`.

!!! notice ""
    Notice that we need to call the repository to make it aware of alice being renamed to bob. This is because the objects are immutable so it can't magically know about the new object. This may seem like extra work but this is intentional to prevent any accidental persisting of a partially modified entity, everything is [explicit](https://innmind.github.io/documentation/philosophy/explicit/).

    Also notice that we use the same method `put` as inserting a brand new aggregate. The ORM knows that it needs to do an update because it's aware of the `Id` reference since it build it when fetching alice.

And like for persisting an aggregate we return `Either::right(null)` to commit the transaction, even if alice doesn't exist.

??? tip
    If you feel the example is a bit verbose we can shorten it like this:

    ```php
    use Formal\ORM\Id;

    $repository = $orm->repository(User::class);
    $orm->transactional(
        static fn() => $repository
            ->get(Id::of(User::class, 'alice-uuid'))
            ->map(static fn(User $alice) => $alice->rename('bob'))
            ->map($repository->put(...))
            ->either(),
        },
    );
    ```

    This does the same thing except one thing. If alice doesn't exist it will rollback the transaction instead of committing it, but this doesn't change the end result.

    `->map($repository->put(...))` this will call the `put` method if there was an alice that has been renamed to bob on the line before. 

    `->either()` this transforms the `Maybe<void>` to an `Either<null, void>`. The `void` type is the return type of the `put` method and the `null` is when alice doesn't exist. That's why we rollback if alice doesn't exist, the returned `Either` contains `null` on the left side.

    Also note that all this is lazy evaluated, the `get` and eventually `put` occur when `transactional` checks the `Either` returned by the callable.
