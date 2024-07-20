# Collection of entities

Another common use cases could be to have an unknown number of entities. Our `User` may want to specify multiple addresses.

You can do it like this:

```php title="User.php" hl_lines="5 11 16 17 22 32 35-42"
use Formal\ORM\{
    Id,
    Definition\Contains,
};
use Innmind\Immutable\Set;

final readonly class User
{
    /**
     * @param Id<self> $id
     * @param Set<Address> $address
     */
    private function __construct(
        private Id $id,
        private Name $name,
        #[Contains(Address::class)]
        private Set $addresses,
    ) {}

    public static function new(Name $name): self
    {
        return new self(Id::new(self::class), $name, Set::of());
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function rename(Name $name): self
    {
        return new self($this->id, $name, $this->addresses);
    }

    public function addAddress(Address $address): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->addresses->add($address),
        );
    }
}
```

This is very similar to [optional entities](optionals.md).

The `Set` monad represents an unordered collection of unique values.

??? warning
    When a `Set` contains objects, the uniqueness is defined by the reference of the objects and not their values.

    This means that these 2 sets are not the same:

    ```php
    $address = new Address('foo', 'bar', 'baz');
    $set1 = Set::of($address, $address);
    $set2 = Set::of(
        new Address('foo', 'bar', 'baz'),
        new Address('foo', 'bar', 'baz'),
    );
    ```

    `$set1` only contains 1 `Address` while `$set2` contains 2.

??? note
    If you're not familiar with the `Set` monad you can head to this [documentation](http://innmind.github.io/Immutable/structures/set/) describing all its methods.
