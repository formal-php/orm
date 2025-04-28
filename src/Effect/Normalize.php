<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

use Formal\ORM\{
    Effect,
    Definition\Aggregate,
    Repository\Normalize\Collection,
    Raw\Aggregate\Collection\Entity,
    Specification,
};
use Innmind\Specification\Comparator;
use Innmind\Reflection\Extract;
use Innmind\Immutable\{
    Map,
    Sequence,
};

/**
 * @internal
 * @psalm-immutable
 * @template T of object
 */
final class Normalize
{
    /** @var Aggregate<T> */
    private Aggregate $definition;
    /** @var Specification\Normalize<T> */
    private Specification\Normalize $normalizeSpecification;
    /** @var Map<non-empty-string, Aggregate\Property<T, mixed>> */
    private Map $properties;
    /** @var Map<non-empty-string, Map<non-empty-string, Aggregate\Property>> */
    private Map $entities;
    /** @var Map<non-empty-string, Map<non-empty-string, Aggregate\Property>> */
    private Map $optionals;
    /** @var Map<non-empty-string, Collection> */
    private Map $collections;

    /**
     * @param Aggregate<T> $definition
     * @param Specification\Normalize<T> $normalizeSpecification
     */
    private function __construct(
        Aggregate $definition,
        Specification\Normalize $normalizeSpecification,
    ) {
        $this->definition = $definition;
        $this->normalizeSpecification = $normalizeSpecification;
        $this->properties = Map::of(
            ...$definition
                ->properties()
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );
        $this->entities = Map::of(
            ...$definition
                ->entities()
                ->map(
                    static fn($entity) => [
                        $entity->name(),
                        Map::of(
                            ...$entity
                                ->properties()
                                ->map(static fn($property) => [$property->name(), $property])
                                ->toList(),
                        ),
                    ],
                )
                ->toList(),
        );
        $this->optionals = Map::of(
            ...$definition
                ->optionals()
                ->map(
                    static fn($optional) => [
                        $optional->name(),
                        Map::of(
                            ...$optional
                                ->properties()
                                ->map(static fn($property) => [$property->name(), $property])
                                ->toList(),
                        ),
                    ],
                )
                ->toList(),
        );
        $extract = new Extract;
        $this->collections = Map::of(
            ...$definition
                ->collections()
                ->map(
                    static fn($collection) => [
                        $collection->name(),
                        Collection::of($collection, $extract),
                    ],
                )
                ->toList(),
        );
    }

    public function __invoke(Effect $effect): Normalized
    {
        return $effect->normalize(
            $this->normalizeProperty(...),
            $this->normalizeEntity(...),
            $this->normalizeOptional(...),
            $this->normalizeChildAdd(...),
            $this->normalizeChildRemove(...),
        );
    }

    /**
     * @internal
     * @psalm-pure
     * @template A of object
     *
     * @param Aggregate<A> $definition
     * @param Specification\Normalize<A> $normalizeSpecification
     *
     * @return self<A>
     */
    public static function of(
        Aggregate $definition,
        Specification\Normalize $normalizeSpecification,
    ): self {
        return new self($definition, $normalizeSpecification);
    }

    private function normalizeProperty(Property $effect): Normalized\Property
    {
        $property = $effect->property();

        return $this
            ->properties
            ->get($property)
            ->map(
                static fn($property) => $property
                    ->type()
                    ->normalize($effect->value()),
            )
            ->match(
                static fn($value) => Normalized\Property::assign(
                    $property,
                    $value,
                ),
                static fn() => throw new \LogicException("Unknown property '$property'"),
            );
    }

    /**
     * @param non-empty-string $entity
     * @param Sequence<Property> $properties
     *
     * @return Sequence<Normalized\Property>
     */
    private function normalizeEntity(
        string $entity,
        Sequence $properties,
    ): Sequence {
        return $this
            ->entities
            ->get($entity)
            ->map(static fn($entity) => $properties->map(
                static fn($effect) => $entity
                    ->get($effect->property())
                    ->map(
                        static fn($property) => $property
                            ->type()
                            ->normalize($effect->value()),
                    )
                    ->map(static fn($value) => Normalized\Property::assign(
                        $effect->property(),
                        $value,
                    ))
                    ->match(
                        static fn($effect) => $effect,
                        static fn() => throw new \LogicException("Unknown property '{$effect->property()}'"),
                    ),
            ))
            ->match(
                static fn($effect) => $effect,
                static fn() => throw new \LogicException("Unknown property '$entity'"),
            );
    }

    /**
     * @param non-empty-string $optional
     * @param Sequence<Property> $properties
     *
     * @return Sequence<Normalized\Property>
     */
    private function normalizeOptional(
        string $optional,
        Sequence $properties,
    ): Sequence {
        return $this
            ->optionals
            ->get($optional)
            ->map(static fn($optional) => $properties->map(
                static fn($effect) => $optional
                    ->get($effect->property())
                    ->map(
                        static fn($property) => $property
                            ->type()
                            ->normalize($effect->value()),
                    )
                    ->map(static fn($value) => Normalized\Property::assign(
                        $effect->property(),
                        $value,
                    ))
                    ->match(
                        static fn($effect) => $effect,
                        static fn() => throw new \LogicException("Unknown property '{$effect->property()}'"),
                    ),
            ))
            ->match(
                static fn($effect) => $effect,
                static fn() => throw new \LogicException("Unknown property '$optional'"),
            );
    }

    /**
     * @param non-empty-string $collection
     * @param Sequence<object> $entities
     *
     * @return Sequence<Entity>
     */
    private function normalizeChildAdd(
        string $collection,
        Sequence $entities,
    ): Sequence {
        return $this
            ->collections
            ->get($collection)
            ->map(
                static fn($collection) => $collection(
                    $entities->toSet(),
                ),
            )
            ->match(
                static fn($effects) => $effects->entities()->unsorted(),
                static fn() => throw new \LogicException("Unknown property '$collection'"),
            );
    }

    /**
     * @param non-empty-string $collection
     */
    private function normalizeChildRemove(
        string $collection,
        Comparator $specification,
    ): Specification\Property {
        $specification = ($this->normalizeSpecification)(Specification\Child::of(
            $collection,
            $specification,
        ));

        /**
         * Implicit behaviour, see Specification\Normalize
         * @psalm-suppress UndefinedInterfaceMethod
         * @var Specification\Property
         */
        return $specification->specification();
    }
}
