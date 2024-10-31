---
hide:
    - navigation
---

# Known issues

## Mapping

### Aggregate name collision

By default the ORM translate an Aggregate class to a simple name in the [adapter](adapters/index.md). For example the class `App\Domain\User` is translated to `user`. This allows to simplify reading the storage folders/tables/indexes.

For small projects this is fine. But for larger projects names collision may arise.

For example you may have the aggregates `App\Domain\Shipping\Product` and `App\Domain\Billing\Product` that would result in the same `product` name in the storage.

You can fix it like this:

```php
use Formal\ORM\{
    Manager,
    Definition\Aggregates,
    Definition\Types,
};

$orm = Manager::of(
    /* your storage adapter (1) */,
    Aggregates::of(
        Types::default(),
    )->mapName(static fn(string $class) {
        \App\Domain\Shipping\Product::class => 'shippingProduct',
        \App\Domain\Billing\Product::class => 'billingProduct',
    }),
);
```

1. see [Adapters](adapters/index.md)

!!! info ""
    This also allows to fix the default casing of names. For example the class `App\Domain\DocumentTemplate` result in the name `documenttemplate`. Which is not very readable.

    This behaviour won't be change for the time being to not break existing projects. But you can gradually fix this via the `mapName` method.

### Floating points

By default Formal doesn't support `float` as a property type _but_ it allows you to use it if you need to.

Storing `float`s is a tricky business because of the decimal part precision. Not every system will represent the same value the same way and you may end up with implicit convertions. Meaning you may not retrieve the exact value you put in.

Formal tries to be as explicit as it can be. Supporting by default a type with implicit behaviours is a sure way to bugs.

That being said you can still create a [custom type](mapping/type.md) and normalize that type to a `float` at the storage level. This way you have to determine how to represent the value in your storage and can control the precision.

??? info
    If you're not familiar with the `float` problems, you can read this, rather long, [article from Oracle](https://docs.oracle.com/cd/E19957-01/806-3568/ncg_goldberg.html) on how floating points are represented by a system and errors associated.

    You can also watch this [talk](https://www.youtube.com/watch?v=0iEKP4tsWe0) (in french).

## Elasticsearch

### Searching with `endsWith`

When searching aggregates via a [specification](specifications/index.md) with `Sign::endsWith` you may not always see all the results.

Internally this search uses the [`wildcard` query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html) with the value starting with `*`. As described in the documentation this **SHOULD NOT** be used as it's an expensive query.

If you really need to do this kind of search you could add an extra property on your aggregate with the string being in reversed order from the original one. You can then do a search on this property with `Sign::startsWith` and reversing the string used as argument.

!!! warning ""
    Bear in mind that `startsWith` also uses the `wildcard` query and may be slower that you'd want or even not return the results you'd expect.
