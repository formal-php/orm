<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
};
use Innmind\Validation\{
    Constraint,
    Shape,
    Is,
    Each,
    Of,
};
use Innmind\Immutable\{
    Maybe,
    Sequence,
    Validation,
    Set,
};

/**
 * @internal
 * @template T of object
 */
final class Decode
{
    /** @var Definition<T> */
    private Definition $definition;
    /** @var Constraint<mixed, Sequence<Aggregate\Property>> */
    private Constraint $properties;
    /** @var Constraint<array, Sequence<Aggregate\Entity>> */
    private Constraint $entities;
    /** @var Constraint<array, Sequence<Aggregate\Optional>> */
    private Constraint $optionals;
    /** @var Constraint<array, Sequence<Aggregate\Collection>> */
    private Constraint $collections;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->properties = self::properties($definition->properties());
        /** @var Constraint<array, Sequence<Aggregate\Entity>> */
        $this->entities = $definition
            ->entities()
            ->match(
                static fn($entity, $entities) => $entities
                    ->reduce(
                        Shape::of(
                            $entity->name(),
                            self::entity($entity),
                        ),
                        static fn(Shape $constraint, $entity) => $constraint->with(
                            $entity->name(),
                            self::entity($entity),
                        ),
                    )
                    ->map(static fn(array $entities) => Sequence::of(
                        ...\array_values($entities),
                    )),
                static fn() => Of::callable(static fn() => Validation::success(Sequence::of())),
            );
        /** @var Constraint<array, Sequence<Aggregate\Optional>> */
        $this->optionals = $definition
            ->optionals()
            ->match(
                static fn($optional, $optionals) => $optionals
                    ->reduce(
                        Shape::of(
                            $optional->name(),
                            self::optional($optional),
                        ),
                        static fn(Shape $constraint, $optional) => $constraint->with(
                            $optional->name(),
                            self::optional($optional),
                        ),
                    )
                    ->map(static fn(array $optionals) => Sequence::of(
                        ...\array_values($optionals),
                    )),
                static fn() => Of::callable(static fn() => Validation::success(Sequence::of())),
            );
        /** @var Constraint<array, Sequence<Aggregate\Collection>> */
        $this->collections = $definition
            ->collections()
            ->match(
                static fn($collection, $collections) => $collections
                    ->reduce(
                        Shape::of(
                            $collection->name(),
                            self::collection($collection),
                        ),
                        static fn(Shape $constraint, $collection) => $constraint->with(
                            $collection->name(),
                            self::collection($collection),
                        ),
                    )
                    ->map(static fn(array $collections) => Sequence::of(
                        ...\array_values($collections),
                    )),
                static fn() => Of::callable(static fn() => Validation::success(Sequence::of())),
            );
    }

    /**
     * @return callable(mixed): Maybe<Aggregate>
     */
    public function __invoke(Aggregate\Id $id = null): callable
    {
        $property = $this->definition->id()->property();
        /**
         * @psalm-suppress MixedArgument
         * @var Constraint<array, Aggregate\Id>
         */
        $id = match ($id) {
            null => Shape::of(
                $property,
                Is::string(),
            )->map(static fn(array $content) => Aggregate\Id::of(
                $property,
                $content[$property],
            )),
            default => Of::callable(static fn() => Validation::success($id)),
        };

        return fn(mixed $content) => Maybe::all(
            Is::array()->and($id)($content)->maybe(),
            ($this->properties)($content)->maybe(),
            Is::array()->and($this->entities)($content)->maybe(),
            Is::array()->and($this->optionals)($content)->maybe(),
            Is::array()->and($this->collections)($content)->maybe(),
        )->map(Aggregate::of(...));
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
     * @return Constraint<mixed, Aggregate\Entity>
     */
    private static function entity(Definition\Entity $entity): Constraint
    {
        return self::properties($entity->properties())->map(
            static fn($properties) => Aggregate\Entity::of(
                $entity->name(),
                $properties,
            ),
        );
    }

    /**
     * @return Constraint<mixed, Aggregate\Optional>
     */
    private static function optional(Definition\Optional $optional): Constraint
    {
        return Is::null()
            ->or(self::properties($optional->properties()))
            ->map(Maybe::of(...))
            ->map(
                static fn($properties) => Aggregate\Optional::of(
                    $optional->name(),
                    $properties,
                ),
            );
    }

    /**
     * @return Constraint<mixed, Aggregate\Collection>
     */
    private static function collection(Definition\Collection $collection): Constraint
    {
        /** @psalm-suppress MixedArgument Due to the array keys */
        return Is::array()
            ->and(Is::list())
            ->and(Each::of(
                Shape::of(
                    'data',
                    self::properties($collection->properties()),
                )
                    ->map(static fn(array $entity) => Aggregate\Collection\Entity::of(
                        $entity['data'],
                    )),
            ))
            ->map(static fn($entities) => Set::of(...$entities))
            ->map(
                static fn($entities) => Aggregate\Collection::of(
                    $collection->name(),
                    $entities,
                ),
            );
    }

    /**
     * @param Sequence<Definition\Property> $properties
     *
     * @return Constraint<mixed, Sequence<Aggregate\Property>>
     */
    private static function properties(Sequence $properties): Constraint
    {
        /** @var Constraint<mixed, Sequence<Aggregate\Property>> */
        return $properties->match(
            static fn($property, $properties) => Is::array()->and(
                $properties
                    ->reduce(
                        Shape::of($property->name(), self::property($property)),
                        static fn(Shape $constraint, $property) => $constraint->with(
                            $property->name(),
                            self::property($property),
                        ),
                    )
                    ->map(static fn(array $properties) => Sequence::of(
                        ...\array_values($properties),
                    )),
            ),
            static fn() => Of::callable(static fn() => Validation::success(Sequence::of())),
        );
    }

    /**
     * @return Constraint<mixed, Aggregate\Property>
     */
    private static function property(Definition\Property $property): Constraint
    {
        return Is::null()
            ->or(Is::string())
            ->or(Is::int())
            ->or(Is::bool())
            ->map(static fn($value) => Aggregate\Property::of(
                $property->name(),
                $value,
            ));
    }
}
