<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw;

use Innmind\Immutable\Set;

final class Aggregate
{
    private Aggregate\Id $id;
    /** @var Set<Aggregate\Property> */
    private Set $properties;

    /**
     * @param Set<Aggregate\Property> $properties
     */
    private function __construct(Aggregate\Id $id, Set $properties)
    {
        $this->id = $id;
        $this->properties = $properties;
    }

    /**
     * @param Set<Aggregate\Property> $properties
     */
    public static function of(Aggregate\Id $id, Set $properties): self
    {
        return new self($id, $properties);
    }

    public function id(): Aggregate\Id
    {
        return $this->id;
    }

    /**
     * @return Set<Aggregate\Property>
     */
    public function properties(): Set
    {
        return $this->properties;
    }
}
