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
    private function __construct(
        Definition $definition,
        KnownCollectionEntity $knownCollectionEntity,
    ) {
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
                    $knownCollectionEntity,
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

        return fn(Aggregate $data) => Denormalized::of(
            $id = $denormalize($data->id()),
            $this->properties($id, $data),
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
    public static function of(
        Definition $definition,
        KnownCollectionEntity $knownCollectionEntity,
    ): self {
        return new self($definition, $knownCollectionEntity);
    }

    /**
     * @return Map<non-empty-string, mixed>
     */
    private function properties(Id $id, Aggregate $data): Map
    {
        return Map::of(
            ...$data
                ->properties()
                ->flatMap(
                    fn($property) => $this
                        ->properties
                        ->get($property->name())
                        ->map(static fn($definition): mixed => $definition->type()->denormalize($property->value()))
                        ->map(static fn($value) => [$property->name(), $value])
                        ->toSequence(),
                )
                ->toList(),
            ...$data
                ->entities()
                ->flatMap(
                    fn($entity) => $this
                        ->entities
                        ->get($entity->name())
                        ->map(static fn($denormalize): object => $denormalize($entity))
                        ->map(static fn($value) => [$entity->name(), $value])
                        ->toSequence(),
                )
                ->toList(),
            ...$data
                ->optionals()
                ->flatMap(
                    fn($optional) => $this
                        ->optionals
                        ->get($optional->name())
                        ->map(static fn($denormalize): Maybe => $denormalize($optional))
                        ->map(static fn($value) => [$optional->name(), $value])
                        ->toSequence(),
                )
                ->toList(),
            ...$data
                ->collections()
                ->flatMap(
                    fn($collection) => $this
                        ->collections
                        ->get($collection->name())
                        ->map(static fn($denormalize): Set => $denormalize($id, $collection))
                        ->map(static fn($value) => [$collection->name(), $value])
                        ->toSequence(),
                )
                ->toList(),
        );
    }
}
