# Two-phase commit

This case explores a race condition where a `Product` is being renamed, but between the check for the name and the persist another `Product` with the same name is inserted. In such case we want to rollback the renaming.

=== "Script"
    ```php
    use Formal\ORM\Id;
    use Innmind\Specification\{
        Comparator\Property,
        Sign,
    };

    $repository = $orm->repository(Product::class);
    $hasNewName = Property::of(
        'name',
        Sign::equality,
        'New name',
    );
    $productWithOldName = $orm->transactional(
        static fn() => $repository
            ->get(Id::of(Product::class, 'some-uuid'))
            ->filter(static fn() => $repository->none($hasNewName))
            ->flatMap(
                static fn($product) => $repository
                    ->put($product->rename('New name'))
                    ->map(static fn() => $product)
                    ->maybe(),
            )
            ->either(),
    )->match(
        static fn($product) => $product,
        static fn() => throw new \Exception('Product does not exist, could not be persisted or name already used'),
    );

    if ($repository->size($hasNewName) === 1) {
        // no other product has been inserted with the same name
        return;
    }

    $orm->transactional(
        static fn() => $repository
            ->put($productWithOldName)
            ->either(),
    );
    ```

=== "`Product`"
    ```php
    final readonly class Product
    {
        /**
         * @param Id<self> $id
         */
        public function __construct(
            private Id $id,
            private string $name,
        ) {
        }

        public function rename(string $name): self
        {
            return new self($this->id, $name);
        }
    }
    ```


!!! success ""
    Because aggregates are immutable, rollbacking one to an old state is as simple as persisting the original object fetched from the storage.
