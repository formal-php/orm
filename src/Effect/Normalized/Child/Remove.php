<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Normalized\Child;

use Formal\ORM\Specification\Property;

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
        private Property $specification,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(string $property, Property $specification): self
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

    public function specification(): Property
    {
        return $this->specification;
    }
}
