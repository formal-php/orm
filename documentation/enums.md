---
hide:
    - navigation
    - toc
---

# Enums

!!! success ""
    Formal natively support enums as a property and inside collections (1).
    {.annotate}

    1. Via [`Set`s](mapping/collections.md).

This means you don't need to create [custom types](mapping/type.md) for each enum in your project, it just works.

??? info
    Formal uses the `case` name as the value persisted in the storage. Even when you use backed enums.

!!! tip ""
    In order to search aggregates having an enum case inside a collection you can use this [specification](specifications/index.md):

    === "Find one"
        ```php
        use Formal\ORM\Specification\Child\Enum;

        Enum::any(
            'collectionPropertyName',
            YourEnum::someCase,
        )
        ```

        This will return the aggregate if this case is present in the collection.

    === "Find amongst many"
        ```php
        use Formal\ORM\Specification\Child\Enum;

        Enum::in(
            'collectionPropertyName',
            YourEnum::someCase,
            YourEnum::someOtherCase,
        )
        ```

        This will return the aggregate if one of the cases is present in the collection.
