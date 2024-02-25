<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate\Collection;

use Formal\ORM\Raw\Aggregate\Property;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Entity
{
    /** @var Sequence<Property> */
    private Sequence $properties;

    /**
     * @param Sequence<Property> $properties
     */
    private function __construct(Sequence $properties)
    {
        $this->properties = $properties;
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Property> $properties
     */
    public static function of(Sequence $properties): self
    {
        return new self($properties);
    }

    /**
     * @return Sequence<Property>
     */
    public function properties(): Sequence
    {
        return $this->properties;
    }
}
