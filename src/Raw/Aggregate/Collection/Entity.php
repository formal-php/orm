<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate\Collection;

use Formal\ORM\Raw\Aggregate\{
    Property,
    Collection\Entity\Reference,
};
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Entity
{
    private Reference $reference;
    /** @var Sequence<Property> */
    private Sequence $properties;

    /**
     * @param Sequence<Property> $properties
     */
    private function __construct(Reference $reference, Sequence $properties)
    {
        $this->reference = $reference;
        $this->properties = $properties;
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Property> $properties
     */
    public static function of(Reference $reference, Sequence $properties): self
    {
        return new self($reference, $properties);
    }

    public function reference(): Reference
    {
        return $this->reference;
    }

    /**
     * @return Sequence<Property>
     */
    public function properties(): Sequence
    {
        return $this->properties;
    }
}
