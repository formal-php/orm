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
    In order to search aggregates having an enum case inside a collection your [specification](specifications/index.md) must look like this:

    ```php title="SeachByEnum.php" hl_lines="25"
    use Innmind\Specification\{
        Comparator,
        Composable,
        Sign,
    };

    /**
     * @psalm-immutable
     */
    final readonly class SearchByEnum implements Comparator
    {
        use Composable;

        private function __construct(
            private string $name,
        ) {}

        public static function of(YourEnum $case): self
        {
            return new self($case->name);
        }

        public function property(): string
        {
            return 'name'; // This can't be changed! (1)
        }

        public function sign(): Sign
        {
            return Sign::equality;
        }

        public function value(): string
        {
            return $this->name;
        }
    }
    ```

    1. This is the name used internally to store the enum case name when inside a collection.

    And you use it like this:

    ```php
    use Formal\ORM\Specification\Child;

    Child::of(
        'collectionPropertyName',
        SearchByEnum::of(YourEnum::someCase),
    );
    ```
