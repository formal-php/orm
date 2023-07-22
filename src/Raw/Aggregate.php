<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw;

use Innmind\Immutable\{
    Set,
    Map,
    Maybe,
};

final class Aggregate
{
    private Aggregate\Id $id;
    /** @var Set<Aggregate\Property> */
    private Set $properties;
    /** @var Set<Aggregate\Entity> */
    private Set $entities;
    /** @var Map<non-empty-string, Aggregate\Property> */
    private Map $denormalizedProperties;
    /** @var Map<non-empty-string, Aggregate\Entity> */
    private Map $denormalizedEntities;

    /**
     * @param Set<Aggregate\Property> $properties
     * @param Set<Aggregate\Entity> $entities
     */
    private function __construct(
        Aggregate\Id $id,
        Set $properties,
        Set $entities,
    ) {
        $this->id = $id;
        $this->properties = $properties;
        $this->entities = $entities;
        $this->denormalizedProperties = Map::of(
            ...$properties
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );
        $this->denormalizedEntities = Map::of(
            ...$entities
                ->map(static fn($entity) => [$entity->name(), $entity])
                ->toList(),
        );
    }

    /**
     * @param Set<Aggregate\Property> $properties
     * @param Set<Aggregate\Entity> $entities
     */
    public static function of(
        Aggregate\Id $id,
        Set $properties,
        Set $entities,
    ): self {
        return new self($id, $properties, $entities);
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
     * @param non-empty-string $name
     *
     * @return Maybe<Aggregate\Property>
     */
    public function property(string $name): Maybe
    {
        return $this->denormalizedProperties->get($name);
    }

    /**
     * @param non-empty-string $name
     *
     * @return Maybe<Aggregate\Entity>
     */
    public function entity(string $name): Maybe
    {
        return $this->denormalizedEntities->get($name);
    }
}
