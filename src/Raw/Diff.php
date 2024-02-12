<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw;

use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Diff
{
    private Aggregate\Id $id;
    /** @var Sequence<Aggregate\Property> */
    private Sequence $properties;
    /** @var Sequence<Aggregate\Entity> */
    private Sequence $entities;
    /** @var Sequence<Aggregate\Optional|Aggregate\Optional\BrandNew> */
    private Sequence $optionals;
    /** @var Sequence<Aggregate\Collection> */
    private Sequence $collections;

    /**
     * @param Sequence<Aggregate\Property> $properties
     * @param Sequence<Aggregate\Entity> $entities
     * @param Sequence<Aggregate\Optional|Aggregate\Optional\BrandNew> $optionals
     * @param Sequence<Aggregate\Collection> $collections
     */
    private function __construct(
        Aggregate\Id $id,
        Sequence $properties,
        Sequence $entities,
        Sequence $optionals,
        Sequence $collections,
    ) {
        $this->id = $id;
        $this->properties = $properties;
        $this->entities = $entities;
        $this->optionals = $optionals;
        $this->collections = $collections;
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param Sequence<Aggregate\Property> $properties
     * @param Sequence<Aggregate\Entity> $entities
     * @param Sequence<Aggregate\Optional|Aggregate\Optional\BrandNew> $optionals
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
     * @return Sequence<Aggregate\Optional|Aggregate\Optional\BrandNew>
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
