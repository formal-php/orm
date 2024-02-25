<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw,
};
use Innmind\Reflection\Extract;
use Innmind\Immutable\{
    Map,
    Sequence,
};

/**
 * The diff relies on the immutable nature of aggregates and the properties
 * being strictly typed
 *
 * This allows to not unwrap monadic types and accidently loading unncessary
 * data
 *
 * @internal
 * @template T of object
 */
final class Diff
{
    /** @var Definition<T> */
    private Definition $definition;
    private Extract $extract;
    /** @var Map<non-empty-string, Normalize\Entity> */
    private Map $normalizeEntity;
    /** @var Map<non-empty-string, Normalize\Optional> */
    private Map $normalizeOptional;
    /** @var Map<non-empty-string, Normalize\Collection> */
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
                ->map(fn($entity) => [$entity->name(), Normalize\Entity::of(
                    $entity,
                    $this->extract,
                )])
                ->toList(),
        );
        $this->normalizeOptional = Map::of(
            ...$definition
                ->optionals()
                ->map(fn($optional) => [$optional->name(), Normalize\Optional::of(
                    $optional,
                    $this->extract,
                )])
                ->toList(),
        );
        $this->normalizeCollection = Map::of(
            ...$definition
                ->collections()
                ->map(fn($collection) => [$collection->name(), Normalize\Collection::of(
                    $collection,
                    $this->extract,
                )])
                ->toList(),
        );
    }

    /**
     * @param Denormalized<T> $then
     * @param Denormalized<T> $now
     */
    public function __invoke(Denormalized $then, Denormalized $now): Raw\Diff
    {
        $normalizedId = $this->definition->id()->normalize($now->id());
        $then = $then->properties();
        $now = $now->properties();

        // Diffing on denormalized values that has to be immutable we allow to
        // not unwrap monads (such as Maybe for optionals) unless necessary,
        // thus avoiding possible roundtrips to the storage adapter
        $diff = $now
            ->flatMap(
                static fn($name, $nowValue) => $then
                    ->get($name)
                    ->map(static fn($thenValue) => Diff\Property::of(
                        $name,
                        $thenValue,
                        $nowValue,
                    ))
                    ->match(
                        static fn($property) => Map::of([$name, $property]),
                        static fn() => Map::of(),
                    ),
            )
            ->filter(static fn($_, $property) => $property->changed())
            ->values();

        $properties = $diff->flatMap(
            fn($value) => $this
                ->definition
                ->properties()
                ->find(static fn($property) => $property->name() === $value->name())
                ->map(static fn($property) => Raw\Aggregate\Property::of(
                    $property->name(),
                    $property->type()->normalize($value->now()),
                ))
                ->toSequence(),
        );
        /** @psalm-suppress MixedArgument */
        $entities = $diff->flatMap(
            fn($value) => $this
                ->normalizeEntity
                ->get($value->name())
                ->map(static fn($normalize) => self::diffEntities(
                    $normalize($value->then()),
                    $normalize($value->now()),
                ))
                ->toSequence(),
        );
        /** @psalm-suppress MixedArgument */
        $optionals = $diff->flatMap(
            fn($value) => $this
                ->normalizeOptional
                ->get($value->name())
                ->map(static fn($normalize) => self::diffOptionals(
                    $normalize($value->then()),
                    $normalize($value->now()),
                ))
                ->toSequence(),
        );
        /** @psalm-suppress MixedArgument */
        $collections = $diff->flatMap(
            fn($value) => $this
                ->normalizeCollection
                ->get($value->name())
                ->map(static fn($normalize) => $normalize($value->now()))
                ->toSequence(),
        );

        return Raw\Diff::of(
            $normalizedId,
            $properties,
            $entities,
            $optionals,
            $collections,
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
     * @psalm-pure
     */
    private static function diffEntities(
        Raw\Aggregate\Entity $then,
        Raw\Aggregate\Entity $now,
    ): Raw\Aggregate\Entity {
        return Raw\Aggregate\Entity::of(
            $then->name(),
            self::diffProperties($then->properties(), $now->properties()),
        );
    }

    /**
     * @psalm-pure
     */
    private static function diffOptionals(
        Raw\Aggregate\Optional $then,
        Raw\Aggregate\Optional $now,
    ): Raw\Aggregate\Optional|Raw\Aggregate\Optional\BrandNew {
        $diff = Raw\Aggregate\Optional::of(
            $then->name(),
            $now
                ->properties()
                ->map(
                    static fn($properties) => $then
                        ->properties()
                        ->match(
                            static fn($then) => self::diffProperties($then, $properties),
                            static fn() => $properties,
                        ),
                ),
        );

        return $then->properties()->match(
            static fn() => $diff,
            static fn() => Raw\Aggregate\Optional\BrandNew::of($diff),
        );
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Raw\Aggregate\Property> $then
     * @param Sequence<Raw\Aggregate\Property> $now
     *
     * @return Sequence<Raw\Aggregate\Property>
     */
    private static function diffProperties(Sequence $then, Sequence $now): Sequence
    {
        $nowProperties = Map::of(
            ...$now
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );

        return $then->flatMap(
            static fn($then) => $nowProperties
                ->get($then->name())
                ->filter(static fn($now) => $then->value() !== $now->value())
                ->toSequence(),
        );
    }
}
