# Entities

The `User` name represent a _single value_ and you now know to handle this kind of property. But at some point you'll need to use an object that has multiple properties.

This kind of object is called an Entity.

An example for a user would be an `Address`:

```php title="Address.php"
final readonly class Address
{
    public function __construct(
        private string $street,
        private string $zipCode,
        private string $city,
    ) {}

    // (1)
}
```

1. No methods such as getters for conciseness of the example.

And to use it in `User`:

```php title="User.php" hl_lines="11 14 16 26"
use Formal\ORM\Id;

final readonly class User
{
    /**
     * @param Id<self> $id
     */
    private function __construct(
        private Id $id,
        private Name $name,
        private Address $address,
    ) {}

    public static function new(Name $name, Address $address): self
    {
        return new self(Id::new(self::class), $name, $address);
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function rename(Name $name): self
    {
        return new self($this->id, $name, $this->address);
    }
}
```

And that's it!

Thanks to immutability Formal knows for sure that an `Address` only belongs to a `User`, so no need for the `Address` to have an id.

??? warning
    Formal's implementation, while providing a high level abstraction, aims to remain simple.

    For that reason only an Aggregate can have entities. You can't define an Entity inside an Entity!

    This choice is also to force you to really think about the design of your aggregates. If you try to nest entities then maybe you're not using the right approach. And if you really want to use nesting then Formal is not the right abstraction!
