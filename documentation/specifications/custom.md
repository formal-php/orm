# Create first specification

Let's create a specification to target users by their name:

```php title="SearchByName.php"
use Innmind\Specification\{
    Comparator,
    Composable,
    Sign,
};

/**
 * @psalm-immutable
 */
final readonly class SearchByName implements Comparator
{
    use Composable; //(1)

    private function __construct(
        private Sign $sign,
        private Name $name,
    ) {}

    public static function of(Name $name): self
    {
        return new self(Sign::equality, $name);
    }

    public static function startingWith(Name $name): self
    {
        return new self(Sign::startsWith, $name);
    }

    public function property(): string
    {
        return 'name'; //(2)
    }

    public function sign(): Sign
    {
        return $this->sign;
    }

    public function value(): Name //(3)
    {
        return $this->name;
    }
}
```

1. This trait automatically add the 3 _gates_ allowing you to compose this comparison.
2. This the name of the property on the `User` class.
3. The return type must be the same as the one declared on the Aggregate/Entity property.

With this class you can create the rule:

> all users whose name is `alice` or `bob` or starts with a `j` except `john`

like this:

```php
SearchByName::of(Name::of('alice'))
    ->or(SearchByName::of(Name::of('bob')))
    ->or(SearchByName::startsWith(Name::of('j')))
    ->and(
        SearchByName::of('john')->not(),
    );
```

## Targetting entities

If you want to target users by city you'd have this specification:

```php title="SearchByCity.php"
use Innmind\Specification\{
    Comparator,
    Composable,
    Sign,
};

/**
 * @psalm-immutable
 */
final readonly class SearchByCity implements Comparator
{
    use Composable;

    private function __construct(
        private Sign $sign,
        private string $city,
    ) {}

    public static function of(string $city): self
    {
        return new self(Sign::equality, $city);
    }

    public function property(): string
    {
        return 'city';
    }

    public function sign(): Sign
    {
        return $this->sign;
    }

    public function value(): string
    {
        return $this->city;
    }
}
```

Depending of the kind of entity you'd use this like this:

=== "Required Entity"
    ```php
    use Formal\ORM\Specification\Entity;

    Entity::of('address', SearchByCity::of('Paris'));
    ```

    `address` is the name of the property on `User`

=== "Optional Entity"
    ```php
    use Formal\ORM\Specification\Just;

    Just::of('address', SearchByCity::of('Paris'));
    ```

    `address` is the name of the property on `User`

    If the aggregate doesn't have an address specified then it won't be matched.

    ??? warning
        You **MUST NOT** negate a `Just` specification as it may not produce the results you'd expect. However you can negate the specification inside the `Just`.

        This is due to a behaviour inconsistency in [Elasticsearch](../adapters/elasticsearch.md).

=== "Entity colleciton"
    ```php
    use Formal\ORM\Specification\Just;

    Child::of('addresses', SearchByCity::of('Paris'));
    ```

    `addresses` is the name of the property on `User`

    An aggregate will be matched as long as one address exist with this city.
