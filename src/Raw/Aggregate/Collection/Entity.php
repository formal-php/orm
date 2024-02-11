<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate\Collection;

use Formal\ORM\Raw\Aggregate\{
    Property,
    Collection\Entity\Reference,
};
use Innmind\Immutable\Set;

/**
 * @psalm-immutable
 */
final class Entity
{
    private Reference $reference;
    /** @var Set<Property> */
    private Set $properties;

    /**
     * @param Set<Property> $properties
     */
    private function __construct(Reference $reference, Set $properties)
    {
        $this->reference = $reference;
        $this->properties = $properties;
    }

    /**
     * @psalm-pure
     *
     * @param Set<Property> $properties
     */
    public static function of(Reference $reference, Set $properties): self
    {
        return new self($reference, $properties);
    }

    public function reference(): Reference
    {
        return $this->reference;
    }

    /**
     * @return Set<Property>
     */
    public function properties(): Set
    {
        return $this->properties;
    }
}
