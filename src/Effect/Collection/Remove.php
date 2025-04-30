<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Collection;

use Innmind\Specification\Comparator;

/**
 * @internal
 * @psalm-immutable
 */
final class Remove
{
    /**
     * @param non-empty-string $property
     */
    private function __construct(
        private string $property,
        private Comparator $specification,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(string $property, Comparator $specification): self
    {
        return new self($property, $specification);
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    public function specification(): Comparator
    {
        return $this->specification;
    }
}
