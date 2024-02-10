<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate\Collection;

use Formal\ORM\Raw\Aggregate\Property;
use Innmind\Immutable\Set;

/**
 * @psalm-immutable
 */
final class Entity
{
    /** @var Set<Property> */
    private Set $properties;

    /**
     * @param Set<Property> $properties
     */
    private function __construct(Set $properties)
    {
        $this->properties = $properties;
    }

    /**
     * @psalm-pure
     *
     * @param Set<Property> $properties
     */
    public static function of(Set $properties): self
    {
        return new self($properties);
    }

    /**
     * @return Set<Property>
     */
    public function properties(): Set
    {
        return $this->properties();
    }
}
