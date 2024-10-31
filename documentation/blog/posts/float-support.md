---
authors: [baptouuuu]
date: 2024-10-31
---

# Adding `float` support (kinda...)

In the latest release Formal now allows to store `float`s in any storage.

<!-- more -->

So far Formal didn't allow to use `float`s at the storage level on purpose. Handling this type is [tricky](../../issues.md#floating-points) due to the precision to use, rounding errors and implicit convertions.

By avoiding to support it, it also avoided to bring associated bugs.

However in certain cases we _do_ need to store values as `float`s.

In order to reconcile this need with the intention to not bring the associated bugs, Formal brings a partial support. It now allows to normalize a type to a `float` **but** it doesn't support `float` as a property type.

This means that if you want to represent a tax for example you can't type your aggregate like this:

```php hl_lines="4"
final readonly class SomeAggregate
{
    private Id $id;
    private float $tax;
}
```

By default Formal will ignore this property and won't store it. Instead you need to [write a custom type](../../mapping/type.md):

=== "`Tax`"
    ```php
    final readonly class Tax
    {
        public function __construct(
            private float $value,
        ) {}

        public function toFloat(): float
        {
            return $this->value;
        }
    }
    ```

=== "`SomeAggregate`"
    ```php hl_lines="4"
    final readonly class SomeAggregate
    {
        private Id $id;
        private Tax $tax;
    }
    ```

=== "`TaxType`"
    ```php
    use Formal\ORM\Definition\Type;

    /**
     * @psalm-immutable
     * @implements Type<Tax>
     */
    final class TaxType implements Type
    {
        public function normalize(mixed $value): null|string|int|float|bool
        {
            return $value->toFloat();
        }

        public function denormalize(null|string|int|float|bool $value): mixed
        {
            if (!\is_numeric($value)) { #(1)
                throw new \LogicException("'$value' is not a string");
            }

            return new Tax($value + 0); #(2)
        }
    }
    ```

    1. In SQL the value will be read as a `string`.
    2. The `+ 0` allows to convert the `string` (when read from SQL) to either an `int` or `float` without changing the value.

You can then implement [`SQLType`](../../adapters/sql.md#mapping) or [`ElasticsearchType`](../../adapters/elasticsearch.md#mapping) on `TaxType` to choose the precision to use at the storage level.

??? note
    You can't choose the precision for the [Filesystem adapter](../../adapters/filesystem.md) as the value is stored as a JSON string.

!!! success ""
    With this approach Formal let you use `float`s if you need to and at the same time forces you to think about the precision you want/need.

    _You_ control what's happening, no implicits.
