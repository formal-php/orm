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

## Elasticsearch

### Searching with `endsWith`

When searching aggregates via a [specification](specifications/index.md) with `Sign::endsWith` you may not always see all the results.

Internally this search uses the [`wildcard` query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html) with the value starting with `*`. As described in the documentation this **SHOULD NOT** be used as it's an expensive query.

If you really need to do this kind of search you could add an extra property on your aggregate with the string being in reversed order from the original one. You can then do a search on this property with `Sign::startsWith` and reversing the string used as argument.

!!! warning ""
    Bear in mind that `startsWith` also uses the `wildcard` query and may be slower that you'd want or even not return the results you'd expect.
