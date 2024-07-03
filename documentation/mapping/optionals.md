# Optional entities

In the previous chapter you've seen what an Entity is. But an Entity can't always be required!

For our example, not every `User` wan't to specify an `Address`.

This is how you make it optional:

```php title="User.php" hl_lines="3 5 11 16 17 20 22 35-42"
use Formal\ORM\{
    Id,
    Definition\Contains,
};
use Innmind\Immutable\Maybe;

final readonly class User
{
    /**
     * @param Id<self> $id
     * @param Maybe<Address> $address
     */
    private function __construct(
        private Id $id,
        private Name $name,
        #[Contains(Address::class)]
        private Maybe $address,
    ) {}

    public static function new(Name $name): self
    {
        return new self(Id::new(self::class), $name, Maybe::nothing());
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function rename(Name $name): self
    {
        return new self($this->id, $name, $this->address);
    }

    public function addAddress(Address $address): self
    {
        return new self(
            $this->id,
            $this->name,
            Maybe::just($address),
        );
    }
}
```

The `#!php #[Contains(Address::class)]` tells Formal the kind of Entity contained inside the `Maybe`.

The `Maybe` monad is a way to describe the possible absence of data. This is kind of a nullable type.

??? note
    Formal doesn't use `null` to represent the possible absence of an Entity as it would force it to load all optional entities when fetching the aggregate.

    With `Maybe` it can lazy load them when first used after fetching an aggregate from the storage.

    If you're not familiar with the `Maybe` monad you can start learning it [here](https://innmind.github.io/documentation/getting-started/handling-data/maybe/). You can follow up by reading this [documentation](http://innmind.github.io/Immutable/structures/maybe/) describing all its methods.

??? info
    The `Contains` attribute is here to avoid to have to parse the docblock to extract the information specified for static analysis tools such as [Psalm](http://psalm.dev).

    If a standard emerges between static analysis tools in attributes to specify this kind of information then Formal may migrate to it.
