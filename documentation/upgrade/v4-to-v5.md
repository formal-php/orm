# V4 to V5

## `Repository->put()`

=== "Before"
    ```php
    $repository->put($aggregate);
    ```

=== "After"
    ```php
    $repository->put($aggregate)->unwrap();
    ```

## `Repository->remove()`

=== "Before"
    ```php
    $repository->remove($idOrSpecification);
    ```

=== "After"
    ```php
    $repository->remove($idOrSpecification)->unwrap();
    ```

## `Repository->effect()`

=== "Before"
    ```php
    $repository->effect($effect);
    ```

=== "After"
    ```php
    $repository->effect($effect)->unwrap();
    ```

## Transactions

=== "Before"
    ```php
    use Innmind\Immutable\Either;

    $manager
        ->transactional(static function() {
            if (/* some condition */) {
                return Either::right(new SomeValue);
            }

            return Either::left(new SomeError);
        })
        ->match(
            static fn(SomeValue $value) => domSomething($value),
            static fn(SomeError $value) => domSomething($value),
        );
    ```

=== "After"
    ```php hl_lines="14-15"
    use Formal\ORM\Adapter\Transaction\Failure
    use Innmind\Immutable\Either;

    $manager
        ->transactional(static function() {
            if (/* some condition */) {
                return Either::right(new SomeValue);
            }

            return Either::left(new SomeError);
        })
        ->match(
            static fn(SomeValue $value) => domSomething($value),
            static fn(SomeError|Failure $value) => match (true) {
                $value instanceof Failure => throw $value->unwrap(),
                default => domSomething($value),
            },
        );
    ```

    Errors happening during the transaction commit/rollback are now returned on the left side instead of thrown to let you decide if you prefer using exceptions or a monadic style.
