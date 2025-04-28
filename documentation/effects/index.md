---
hide:
    - navigation
    - toc
---

# Effects

An `Effect` allows to change multiple aggregates directly at the `Adapter` level via native queries.

This avoids to have to fetch each aggregate, change them in PHP and then put them back in the repository. As it would generate a lot of queries and would be quite slow.

You can define the following effects:

=== "A property"
    ```php
    use Formal\ORM\Effect;

    $effect = Effect::property('name')->assign('Alice');
    ```

=== "Properties"
    ```php
    use Formal\ORM\Effect;

    $effect = Effect::property('name')->assign('Alice')->and(
        Effect::property('enabled')->assign(false),
    );
    ```

=== "Entity properties"
    ```php
    use Formal\ORM\Effect;

    $effect = Effect::entity('address')->properties(
        Effect::property('zipCode')->assign('12345')->and(
            Effect::property('city')->assign('Somewhere'),
    );
    ```

=== "Optional entity properties"
    ```php
    use Formal\ORM\Effect;

    $effect = Effect::optional('address')->properties(
        Effect::property('zipCode')->assign('12345')->and(
            Effect::property('city')->assign('Somewhere'),
    );
    ```

    ??? note
        Even if you don't specify it in your specification only aggregates having the entity will be updated. An aggregate that doesn't have the entity won't be updated.

=== "Remove optional entity"
    ```php
    use Formal\ORM\Effect;

    $effect = Effect::optional('address')->nothing();
    ```

=== "Add entity to a collection"
    ```php
    use Formal\ORM\Effect;

    $effect = Effect::collection('address')->add(
        new Address('street', 'zip', 'city'),
    );
    ```

=== "Remove entities from a collection"
    ```php
    use Formal\ORM\Effect;
    use Innmind\Specification\{
        Comparator\Property,
        Sign,
    };

    $effect = Effect::collection('address')->remove(
        Property::of(
            'city',
            Sign::equality,
            'Somewhere'
        ),
    );
    ```

    ??? info
        Out of implementation simplicity, it's only possible to provide a single comparison to remove the entities from the collection.

        This limit may be lifted in the future.

You can then apply the effect like this:

```php
$orm
    ->repository(User::class)
    ->effect(
        Effect::property('name')->assign('Alice'),
    );
```

This example will rename all users in the storage as `Alice`.

You can also use a [specification](../specifications/index.md) to only update a subset of the aggregates.

```php
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};

$orm
    ->repository(User::class)
    ->effect(
        Effect::property('name')->assign('Alice'),
        Property::of(
            'name',
            Sign::equality,
            'Jane',
        ),
    );
```

This will rename all `Jane` users to `Alice`.
