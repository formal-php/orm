<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw;

use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Aggregate
{
    /**
     * @param Sequence<Aggregate\Property> $properties
     * @param Sequence<Aggregate\Entity> $entities
     * @param Sequence<Aggregate\Optional> $optionals
     * @param Sequence<Aggregate\Collection> $collections
     */
    private function __construct(
        private Aggregate\Id $id,
        private Sequence $properties,
        private Sequence $entities,
        private Sequence $optionals,
        private Sequence $collections,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Aggregate\Property> $properties
     * @param Sequence<Aggregate\Entity> $entities
     * @param Sequence<Aggregate\Optional> $optionals
     * @param Sequence<Aggregate\Collection> $collections
     */
    public static function of(
        Aggregate\Id $id,
        Sequence $properties,
        Sequence $entities,
        Sequence $optionals,
        Sequence $collections,
    ): self {
        return new self($id, $properties, $entities, $optionals, $collections);
    }

    public function id(): Aggregate\Id
    {
        return $this->id;
    }

    /**
     * @return Sequence<Aggregate\Property>
     */
    public function properties(): Sequence
    {
        return $this->properties;
    }

    /**
     * @return Sequence<Aggregate\Entity>
     */
    public function entities(): Sequence
    {
        return $this->entities;
    }

    /**
     * @return Sequence<Aggregate\Optional>
     */
    public function optionals(): Sequence
    {
        return $this->optionals;
    }

    /**
     * @return Sequence<Aggregate\Collection>
     */
    public function collections(): Sequence
    {
        return $this->collections;
    }
}
