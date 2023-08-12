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
 * @template T of object
 */
final class Diff
{
    /** @var Definition<T> */
    private Definition $definition;
    private Extract $extract;
    /** @var Set<non-empty-string> */
    private Set $properties;
    /** @var Map<non-empty-string, Normalize\Entity> */
    private Map $normalizeEntity;
    /** @var Map<non-empty-string, Normalize\Optional> */
    private Map $normalizeOptional;
    /** @var Map<non-empty-string, Normalize\Collection> */
    private Map $normalizeCollection;
    /** @var \Closure(T): Raw\Aggregate\Id */
    private \Closure $extractId;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->extract = new Extract;
        $this->properties = $definition
            ->properties()
            ->map(static fn($property) => $property->name())
            ->merge(
                $definition
                    ->entities()
                    ->map(static fn($entity) => $entity->name()),
            )
            ->merge(
                $definition
                    ->optionals()
                    ->map(static fn($optional) => $optional->name()),
            )
            ->merge(
                $definition
                    ->collections()
                    ->map(static fn($collection) => $collection->name()),
            );
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
        $id = $definition->id();
        /**
         * @psalm-suppress InvalidArgument
         * @var \Closure(T): Raw\Aggregate\Id
         */
        $this->extractId = static fn(object $aggregate): Raw\Aggregate\Id => $id->normalize($id->extract($aggregate));
    }

    /**
     * @param T $then
     * @param T $now
     */
    public function __invoke(object $then, object $now): Raw\Diff
    {
        $class = $this->definition->class();
        $id = ($this->extractId)($now);
        $then = ($this->extract)($then, $this->properties)->match(
            static fn($properties) => $properties,
            static fn() => throw new \LogicException("Failed to extract properties from '$class'"),
        );
        $now = ($this->extract)($now, $this->properties)->match(
            static fn($properties) => $properties,
            static fn() => throw new \LogicException("Failed to extract properties from '$class'"),
        );

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
                    ->map(static fn($normalize) => $normalize($value->now())) // TODO diff inside the collection
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

    private static function diffEntities(
        Raw\Aggregate\Entity $then,
        Raw\Aggregate\Entity $now,
    ): Raw\Aggregate\Entity {
        return Raw\Aggregate\Entity::of(
            $then->name(),
            self::diffProperties($then->properties(), $now->properties()),
        );
    }

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
