<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
};
use Innmind\Reflection\Extract;
use Innmind\Immutable\Map;

/**
 * @internal
 * @template T of object
 */
final class Normalize
{
    /** @var Definition<T> */
    private Definition $definition;
    private Extract $extract;
    /** @var Map<Definition\Entity, Normalize\Entity> */
    private Map $normalizeEntity;
    /** @var Map<Definition\Optional, Normalize\Optional> */
    private Map $normalizeOptional;
    /** @var Map<Definition\Collection, Normalize\Collection> */
    private Map $normalizeCollection;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->extract = new Extract;
        $this->normalizeEntity = Map::of(
            ...$definition
                ->entities()
                ->map(fn($entity) => [$entity, Normalize\Entity::of(
                    $entity,
                    $this->extract,
                )])
                ->toList(),
        );
        $this->normalizeOptional = Map::of(
            ...$definition
                ->optionals()
                ->map(fn($optional) => [$optional, Normalize\Optional::of(
                    $optional,
                    $this->extract,
                )])
                ->toList(),
        );
        $this->normalizeCollection = Map::of(
            ...$definition
                ->collections()
                ->map(fn($collection) => [$collection, Normalize\Collection::of(
                    $collection,
                    $this->extract,
                )])
                ->toList(),
        );
    }

    /**
     * @param Denormalized<T> $denormalized
     */
    public function __invoke(Denormalized $denormalized): Aggregate
    {
        $properties = $denormalized->properties();

        /** @psalm-suppress MixedArgument Due to the collection normalization */
        return Aggregate::of(
            $this->definition->id()->normalize($denormalized->id()),
            $this
                ->definition
                ->properties()
                ->flatMap(
                    static fn($property) => $properties
                        ->get($property->name())
                        ->map(static fn($value) => Aggregate\Property::of(
                            $property->name(),
                            $property->type()->normalize($value),
                        ))
                        ->toSequence(),
                ),
            $this
                ->definition
                ->entities()
                ->flatMap(
                    fn($entity) => $this
                        ->normalizeEntity
                        ->get($entity)
                        ->flatMap(
                            static fn($normalize) => $properties
                                ->get($entity->name())
                                ->map($normalize),
                        )
                        ->toSequence(),
                ),
            $this
                ->definition
                ->optionals()
                ->flatMap(
                    fn($optional) => $this
                        ->normalizeOptional
                        ->get($optional)
                        ->flatMap(
                            static fn($normalize) => $properties
                                ->get($optional->name())
                                ->map($normalize),
                        )
                        ->toSequence(),
                ),
            $this
                ->definition
                ->collections()
                ->flatMap(
                    fn($collection) => $this
                        ->normalizeCollection
                        ->get($collection)
                        ->flatMap(
                            static fn($normalize) => $properties
                                ->get($collection->name())
                                ->map(static fn($object) => $normalize($object)),
                        )
                        ->toSequence(),
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
}
