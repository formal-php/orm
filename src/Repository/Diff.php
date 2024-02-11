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
    Set,
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
    private function __construct(
        Definition $definition,
        KnownCollectionEntity $knownCollectionEntity,
    ) {
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
                    $knownCollectionEntity,
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
        $id = $this->definition->id()->normalize($now->id());
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
            ->filter(static fn($_, $property) => $property->changed());

        $properties = $diff
            ->flatMap(
                fn($name, $value) => $this
                    ->definition
                    ->properties()
                    ->find(static fn($property) => $property->name() === $name)
                    ->match(
                        static fn($property) => Map::of([$property, $value]),
                        static fn() => Map::of(),
                    ),
            )
            ->map(static fn($property, $value) => Raw\Aggregate\Property::of(
                $property->name(),
                $property->type()->normalize($value->now()),
            ))
            ->values()
            ->toSet();
        /** @psalm-suppress MixedArgument */
        $entities = $diff
            ->flatMap(
                fn($name, $value) => $this
                    ->normalizeEntity
                    ->get($name)
                    ->map(static fn($normalize) => self::diffEntities(
                        $normalize($value->then()),
                        $normalize($value->now()),
                    ))
                    ->match(
                        static fn($value) => Map::of([$name, $value]),
                        static fn() => Map::of(),
                    ),
            )
            ->values()
            ->toSet();
        /** @psalm-suppress MixedArgument */
        $optionals = $diff
            ->flatMap(
                fn($name, $value) => $this
                    ->normalizeOptional
                    ->get($name)
                    ->map(static fn($normalize) => self::diffOptionals(
                        $normalize($value->then()),
                        $normalize($value->now()),
                    ))
                    ->match(
                        static fn($value) => Map::of([$name, $value]),
                        static fn() => Map::of(),
                    ),
            )
            ->values()
            ->toSet();
        /** @psalm-suppress MixedArgument */
        $collections = $diff
            ->flatMap(
                fn($name, $value) => $this
                    ->normalizeCollection
                    ->get($name)
                    ->map(static fn($normalize) => self::diffCollections(
                        $normalize,
                        $id,
                        $value->then(),
                        $value->now(),
                    ))
                    ->match(
                        static fn($value) => Map::of([$name, $value]),
                        static fn() => Map::of(),
                    ),
            )
            ->values()
            ->toSet();

        return Raw\Diff::of(
            $id,
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
    public static function of(
        Definition $definition,
        KnownCollectionEntity $knownCollectionEntity,
    ): self {
        return new self($definition, $knownCollectionEntity);
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

    private static function diffCollections(
        Normalize\Collection $normalize,
        Raw\Aggregate\Id $id,
        Set $then,
        Set $now,
    ): Raw\Aggregate\Collection {
        $diff = $now->partition(static fn($entity) => $then->contains($entity));
        /** @var Set<object> */
        $unmodified = $diff
            ->get(true)
            ->match(
                static fn($unmodified) => $unmodified,
                static fn() => Set::of(),
            );
        /** @var Set<object> */
        $new = $diff
            ->get(false)
            ->match(
                static fn($new) => $new,
                static fn() => Set::of(),
            );
        $unmodified = $normalize($id, $unmodified);
        $new = $normalize($id, $new);

        return $new->with($unmodified);
    }

    /**
     * @psalm-pure
     *
     * @param Set<Raw\Aggregate\Property> $then
     * @param Set<Raw\Aggregate\Property> $now
     *
     * @return Set<Raw\Aggregate\Property>
     */
    private static function diffProperties(Set $then, Set $now): Set
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
                ->toSequence()
                ->toSet(),
        );
    }
}
