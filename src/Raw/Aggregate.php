<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw;

use Innmind\Immutable\Set;

final class Aggregate
{
    private Aggregate\Id $id;
    /** @var Set<Aggregate\Property> */
    private Set $properties;
    /** @var Set<Aggregate\Entity> */
    private Set $entities;
    /** @var Set<Aggregate\Optional> */
    private Set $optionals;
    /** @var Set<Aggregate\Collection> */
    private Set $collections;

    /**
     * @param Set<Aggregate\Property> $properties
     * @param Set<Aggregate\Entity> $entities
     * @param Set<Aggregate\Optional> $optionals
     * @param Set<Aggregate\Collection> $collections
     */
    private function __construct(
        Aggregate\Id $id,
        Set $properties,
        Set $entities,
        Set $optionals,
        Set $collections,
    ) {
        $this->id = $id;
        $this->properties = $properties;
        $this->entities = $entities;
        $this->optionals = $optionals;
        $this->collections = $collections;
    }

    /**
     * @param Set<Aggregate\Property> $properties
     * @param Set<Aggregate\Entity> $entities
     * @param Set<Aggregate\Optional> $optionals
     * @param Set<Aggregate\Collection> $collections
     */
    public static function of(
        Aggregate\Id $id,
        Set $properties,
        Set $entities,
        Set $optionals,
        Set $collections,
    ): self {
        return new self($id, $properties, $entities, $optionals, $collections);
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

    /**
     * @return Set<Aggregate\Collection>
     */
    public function collections(): Set
    {
        return $this->collections;
    }
}
