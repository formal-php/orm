<?php
declare(strict_types = 1);

namespace Formal\ORM\Specification;

use Innmind\Specification\{
    Specification,
    Composable,
};

/**
 * @psalm-immutable
 */
final class Child implements Specification
{
    use Composable;

    /**
     * @param non-empty-string $collection
     */
    private function __construct(
        private string $collection,
        private Specification $specification,
    ) {
    }

    /**
     * Use this specification to find an aggregate where at least one entity of
     * the specified collection matches the given specification.
     *
     * @psalm-pure
     *
     * @param non-empty-string $collection
     */
    public static function of(string $collection, Specification $specification): self
    {
        return new self($collection, $specification);
    }

    /**
     * @return non-empty-string
     */
    public function collection(): string
    {
        return $this->collection;
    }

    public function specification(): Specification
    {
        return $this->specification;
    }
}
