<?php
declare(strict_types = 1);

namespace Formal\ORM\Sort;

use Formal\ORM\{
    Sort,
    Sort\Property,
};

/**
 * @psalm-immutable
 */
final class Entity
{
    /** @var non-empty-string */
    private string $name;
    private Property $property;

    /**
     * @param non-empty-string $name
     */
    private function __construct(string $name, Property $property)
    {
        $this->name = $name;
        $this->property = $property;
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    public static function of(string $name, Property $property): self
    {
        return new self($name, $property);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function property(): Property
    {
        return $this->property;
    }

    public function direction(): Sort
    {
        return $this->property->direction();
    }
}
