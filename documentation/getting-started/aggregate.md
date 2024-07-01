# Create an aggregate

Throughout this documentation you'll learn how to persist a `User` aggregate. For now this aggregate will only have an id and a name:

```php title="User.php"
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
}
```

There's not much code but there's already much to know!

The class is declared `readonly` to make sure once it's instanciated it can't be modified. This the [immutability](../philosophy.md#immutability) required for the ORM to work properly. (1)
{ .annotate }

1. If you use [Psalm](https://psalm.dev) you can also add the `@psalm-immutable` annotation on the class.

An aggregate **must** have an `$id` property with the type `Formal\ORM\Id`. It's this property that uniquely reference an aggregate in the storage. (You'll also see in the next chapter how the ORM uses it internally).

`Id::new()` will generate a brand new value (1). The class passed as argument allows Psalm to know to which aggregate type it belongs to, this prevents you from mistakenly use an id of an aggregate A when trying to retrieve an aggregate B.
{ .annotate }

1. Internally it uses uuids.

!!! info ""
    For now we'll only use this `string` property, you'll learn in the [mapping chapter](../mapping/index.md) how to use more complex types.

??? tip
    In this example we use a private constructor that list all properties and a public named constructor. While this design is not mandatory it will be clearer to see what's happening when you'll _modify_ the aggregate.

    Also note that the ORM **does not** need your aggregate constructor to be public in order to instanciate objects coming from the storage.
