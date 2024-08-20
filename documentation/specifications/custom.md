# Create first specification

Let's create a specification to target users by their name:

```php title="SearchByName.php"
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};

final class SearchByName
{
    public static function of(Name $name): Property
    {
        return Property::of(
            'name', //(1)
            Sign::equality,
            $name, //(2)
        );
    }

    public static function startingWith(Name $name): Property
    {
        return Property::of(
            'name',
            Sign::startsWith,
            $name,
        );
    }
}
```

1. This is the name of the property on the `User` class.
2. The value type must be the same as the one declared on the Aggregate/Entity property.

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
    Comparator\Property,
    Sign,
};

final class SearchByCity
{
    public static function of(string $city): Property
    {
        return Property::of('city', Sign::equality, $city);
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

    If you only need to know if an entity exist you can use `Has`:

    ```php
    use Formal\ORM\Specification\Has;

    Has::an('address');
    ```

    ??? warning
        You **MUST NOT** negate a `Just` or a `Has` specification as it may not produce the results you'd expect. However you can negate the specification inside the `Just`.

        This is due to a behaviour inconsistency in [Elasticsearch](../adapters/elasticsearch.md).

=== "Entity colleciton"
    ```php
    use Formal\ORM\Specification\Just;

    Child::of('addresses', SearchByCity::of('Paris'));
    ```

    `addresses` is the name of the property on `User`

    An aggregate will be matched as long as one address exist with this city.

## `Sign::in`

When using `Sign::in` the value of the specification must be either an `array`, a [`Set`](http://innmind.github.io/Immutable/structures/set/) or a [`Sequence`](http://innmind.github.io/Immutable/structures/sequence/) containing only values of the same type of the property. There **MUST** be at least one value.
