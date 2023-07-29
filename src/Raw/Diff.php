<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw;

use Innmind\Immutable\Set;

final class Diff
{
    private Aggregate\Id $id;
    /** @var Set<Aggregate\Property> */
    private Set $properties;
    /** @var Set<Aggregate\Entity> */
    private Set $entities;
    /** @var Set<Aggregate\Optional> */
    private Set $optionals;

    /**
     * @param Set<Aggregate\Property> $properties
     * @param Set<Aggregate\Entity> $entities
     * @param Set<Aggregate\Optional> $optionals
     */
    private function __construct(
        Aggregate\Id $id,
        Set $properties,
        Set $entities,
        Set $optionals,
    ) {
        $this->id = $id;
        $this->properties = $properties;
        $this->entities = $entities;
        $this->optionals = $optionals;
    }

    /**
     * @param Set<Aggregate\Property> $properties
     * @param Set<Aggregate\Entity> $entities
     * @param Set<Aggregate\Optional> $optionals
     */
    public static function of(
        Aggregate\Id $id,
        Set $properties,
        Set $entities,
        Set $optionals,
    ): self {
        return new self($id, $properties, $entities, $optionals);
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

    /**
     * @return Set<Aggregate\Entity>
     */
    public function entities(): Set
    {
        return $this->entities;
    }

    /**
     * @return Set<Aggregate\Optional>
     */
    public function optionals(): Set
    {
        return $this->optionals;
    }
}
