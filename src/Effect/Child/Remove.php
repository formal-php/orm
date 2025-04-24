<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Child;

use Innmind\Specification\Comparator;

/**
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
     * @psalm-pure
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
