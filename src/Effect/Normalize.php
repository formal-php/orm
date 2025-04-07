<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

use Formal\ORM\Definition\Aggregate;
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
    /** @var Map<non-empty-string, Aggregate\Property<T, mixed>> */
    private Map $properties;
    /** @var Map<non-empty-string, Map<non-empty-string, Aggregate\Property>> */
    private Map $entities;
    /** @var Map<non-empty-string, Map<non-empty-string, Aggregate\Property>> */
    private Map $optionals;
    /** @var Map<non-empty-string, Map<non-empty-string, Aggregate\Property>> */
    private Map $collections;

    /**
     * @param Aggregate<T> $definition
     */
    private function __construct(Aggregate $definition)
    {
        $this->definition = $definition;
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
        $this->collections = Map::of(
            ...$definition
                ->collections()
                ->map(
                    static fn($collection) => [
                        $collection->name(),
                        Map::of(
                            ...$collection
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
    }

    public function __invoke(Property|Property\Collection|Entity $effect): Normalized\Properties|Normalized\Entity
    {
        if ($effect instanceof Entity) {
            return $this->normalizeEntity($effect);
        }

        if ($effect instanceof Property) {
            $effects = Sequence::of($effect);
        } else {
            $effects = $effect->effects();
        }

        return Normalized\Properties::of(
            $effects->map($this->normalizeProperty(...)),
        );
    }

    /**
     * @internal
     * @psalm-pure
     * @template A of object
     *
     * @param Aggregate<A> $definition
     *
     * @return self<A>
     */
    public static function of(Aggregate $definition): self
    {
        return new self($definition);
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

    private function normalizeEntity(Entity $effect): Normalized\Entity
    {
        $property = $effect->property();

        return $this
            ->entities
            ->get($effect->property())
            ->map(
                static fn($entity) => $effect
                    ->effects()
                    ->effects()
                    ->map(
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
                    ),
            )
            ->map(Normalized\Properties::of(...))
            ->map(static fn($effects) => Normalized\Entity::of(
                $property,
                $effects,
            ))
            ->match(
                static fn($effect) => $effect,
                static fn() => throw new \LogicException("Unknown property '$property'"),
            );
    }
}
