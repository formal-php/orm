# Custom type

The `User` name is typed with a `string`. This means that it can be empty. Let's introduce the `Name` type to make sure it's never empty:

```php title="Name.php"
final readonly class Name
{
    private function __construct(
        private string $value,
    ) {}

    public static function of(string $value): self
    {
        return match ($value) {
            '' => throw new \LogicException('The name cannot be empty'),
            default => new self($value),
        };
    }

    public function toString(): string
    {
        return $this->value;
    }
}
```

We can now refactor the `User` like this:

```php title="User.php" hl_lines="10 13 18 23"
use Formal\ORM\Id;

final readonly class User
{
    /**
     * @param Id<self> $id
     */
    private function __construct(
        private Id $id,
        private Name $name,
    ) {}

    public static function new(Name $name): self
    {
        return new self(Id::new(self::class), $name);
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function rename(Name $name): self
    {
        return new self($this->id, $name);
    }
}
```

And just like that the `User` can't have an empty name.

But for Formal to properly store this `Name` we need to tell it how to convert the object to a primitive value and vice-versa.

```php title="NameType.php"
use Formal\ORM\Definition\Type;

/**
 * @psalm-immutable
 * @implements Type<Name>
 */
final class NameType implements Type
{
    public function normalize(mixed $value): null|string|int|bool
    {
        return $value->toString();
    }

    public function denormalize(null|string|int|bool $value): mixed
    {
        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        return Name::of($value);
    }
}
```

`@implements Type<Name>` allows [Psalm](https://psalm.dev) to know that the `$value` argument of `normalize` is always a `Name` (despite it's `mixed` type), and the return type of `denormalize` must also be a `Name`.

And lastly tell the ORM about this type converter:

```php
use Formal\ORM\{
    Manager,
    Definition\Aggregates,
    Definition\Types,
    Definition\Type\Support,
}

$orm = Manager::of(
    /* any adapter (1) */,
    Aggregates::of(
        Types::of(
            Support::class(Name::class, new NameType),
        ),
    ),
);
```

1. See the [Adapters](../adapters/index.md) chapter to see all the adapters you can use.

!!! success ""
    With this you can also use the `?Name` type on a property.

    Formal handles the `null` case for you!
