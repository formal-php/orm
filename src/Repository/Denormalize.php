<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Definition\Aggregate\Property,
    Raw\Aggregate,
    Id,
};
use Innmind\Reflection\Instanciate;
use Innmind\Immutable\{
    Map,
    Maybe,
    Set,
};

/**
 * @internal
 * @template T of object
 */
final class Denormalize
{
    private Instanciate $instanciate;
    /** @var \Closure(Aggregate\Id): Id<T> */
    private \Closure $denormalizeId;
    /** @var Map<non-empty-string, Property<T, mixed>> */
    private Map $properties;
    /** @var Map<non-empty-string, Denormalize\Entity> */
    private Map $entities;
    /** @var Map<non-empty-string, Denormalize\Optional> */
    private Map $optionals;
    /** @var Map<non-empty-string, Denormalize\Collection> */
    private Map $collections;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->instanciate = new Instanciate;
        /** @var \Closure(Aggregate\Id): Id<T> */
        $this->denormalizeId = $definition->id()->denormalize(...);
        $this->properties = Map::of(
            ...$definition
                ->properties()
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );
        $this->entities = Map::of(
            ...$definition
                ->entities()
                ->map(fn($entity) => [$entity->name(), Denormalize\Entity::of(
                    $entity,
                    $this->instanciate,
                )])
                ->toList(),
        );
        $this->optionals = Map::of(
            ...$definition
                ->optionals()
                ->map(fn($optional) => [$optional->name(), Denormalize\Optional::of(
                    $optional,
                    $this->instanciate,
                )])
                ->toList(),
        );
        $this->collections = Map::of(
            ...$definition
                ->collections()
                ->map(fn($collection) => [$collection->name(), Denormalize\Collection::of(
                    $collection,
                    $this->instanciate,
                )])
                ->toList(),
        );
    }

    /**
     * @param ?Id<T> $id
     *
     * @return callable(Aggregate): Denormalized<T>
     */
    public function __invoke(Id $id = null): callable
    {
        $denormalize = match ($id) {
            null => $this->denormalizeId,
            default => static fn(Aggregate\Id $_) => $id,
        };

        $properties = $this->properties;
        $entities = $this->entities;
        $optionals = $this->optionals;
        $collections = $this->collections;

        return static fn(Aggregate $data) => Denormalized::of(
            $denormalize($data->id()),
            self::properties(
                $data,
                $properties,
                $entities,
                $optionals,
                $collections,
            ),
        );
    }

    /**
     * @internal
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(Definition $definition): self
    {
        return new self($definition);
    }

    /**
     * @param Map<non-empty-string, Property> $properties
     * @param Map<non-empty-string, Denormalize\Entity> $entities
     * @param Map<non-empty-string, Denormalize\Optional> $optionals
     * @param Map<non-empty-string, Denormalize\Collection> $collections
     *
     * @return Map<non-empty-string, mixed>
     */
    private static function properties(
        Aggregate $data,
        Map $properties,
        Map $entities,
        Map $optionals,
        Map $collections,
    ): Map {
        return Map::of(
            ...$data
                ->properties()
                ->flatMap(
                    static fn($property) => $properties
                        ->get($property->name())
                        ->map(static fn($definition): mixed => $definition->type()->denormalize($property->value()))
                        ->map(static fn($value) => [$property->name(), $value])
                        ->toSequence(),
                )
                ->toList(),
            ...$data
                ->entities()
                ->flatMap(
                    static fn($entity) => $entities
                        ->get($entity->name())
                        ->map(static fn($denormalize): object => $denormalize($entity))
                        ->map(static fn($value) => [$entity->name(), $value])
                        ->toSequence(),
                )
                ->toList(),
            ...$data
                ->optionals()
                ->flatMap(
                    static fn($optional) => $optionals
                        ->get($optional->name())
                        ->map(static fn($denormalize): Maybe => $denormalize($optional))
                        ->map(static fn($value) => [$optional->name(), $value])
                        ->toSequence(),
                )
                ->toList(),
            ...$data
                ->collections()
                ->flatMap(
                    static fn($collection) => $collections
                        ->get($collection->name())
                        ->map(static fn($denormalize): Set => $denormalize($collection))
                        ->map(static fn($value) => [$collection->name(), $value])
                        ->toSequence(),
                )
                ->toList(),
        );
    }
}
