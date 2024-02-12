<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Formal\ORM\{
    Id,
    Definition\Contains,
    Definition\Types,
};
use Innmind\Reflection\{
    ReflectionClass,
    ReflectionProperty,
};
use Innmind\Type\ClassName;
use Innmind\Immutable\{
    Maybe,
    Set,
    Sequence,
    Predicate\Instance,
};

/**
 * @internal
 * @template T of object
 */
final class Parsing
{
    /** @var class-string<T> */
    private string $class;
    /** @var Maybe<Identity<T>> */
    private Maybe $id;
    /** @var Sequence<Property<T, mixed>> */
    private Sequence $properties;
    /** @var Sequence<Entity> */
    private Sequence $entities;
    /** @var Sequence<Optional> */
    private Sequence $optionals;
    /** @var Sequence<Collection> */
    private Sequence $collections;

    /**
     * @param class-string<T> $class
     * @param Maybe<Identity<T>> $id
     * @param Sequence<Property<T, mixed>> $properties
     * @param Sequence<Entity> $entities
     * @param Sequence<Optional> $optionals
     * @param Sequence<Collection> $collections
     */
    private function __construct(
        string $class,
        Maybe $id,
        Sequence $properties,
        Sequence $entities,
        Sequence $optionals,
        Sequence $collections,
    ) {
        $this->class = $class;
        $this->id = $id;
        $this->properties = $properties;
        $this->entities = $entities;
        $this->optionals = $optionals;
        $this->collections = $collections;
    }

    /**
     * @internal
     * @template A of object
     *
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function of(string $class): self
    {
        /** @var Maybe<Identity<A>> */
        $id = Maybe::nothing();

        return new self($class, $id, Sequence::of(), Sequence::of(), Sequence::of(), Sequence::of());
    }

    /**
     * @param ReflectionProperty<T> $property
     *
     * @return self<T>
     */
    public function with(ReflectionProperty $property, Types $types): self
    {
        return $this
            ->parse($property, $types)
            ->match(
                fn($parsed) => match ($parsed::class) {
                    Identity::class => new self(
                        $this->class,
                        Maybe::just($parsed), // we can override here because we force the id property to be named "id" so there can only be one
                        $this->properties,
                        $this->entities,
                        $this->optionals,
                        $this->collections,
                    ),
                    Property::class => new self(
                        $this->class,
                        $this->id,
                        ($this->properties)($parsed),
                        $this->entities,
                        $this->optionals,
                        $this->collections,
                    ),
                    Entity::class => new self(
                        $this->class,
                        $this->id,
                        $this->properties,
                        ($this->entities)($parsed),
                        $this->optionals,
                        $this->collections,
                    ),
                    Optional::class => new self(
                        $this->class,
                        $this->id,
                        $this->properties,
                        $this->entities,
                        ($this->optionals)($parsed),
                        $this->collections,
                    ),
                    Collection::class => new self(
                        $this->class,
                        $this->id,
                        $this->properties,
                        $this->entities,
                        $this->optionals,
                        ($this->collections)($parsed),
                    ),
                },
                fn() => $this, // silently discard unparseable properties
            );
    }

    /**
     * @return Maybe<Identity<T>>
     */
    public function id(): Maybe
    {
        return $this->id;
    }

    /**
     * @return Sequence<Property<T, mixed>>
     */
    public function properties(): Sequence
    {
        return $this->properties;
    }

    /**
     * @return Sequence<Entity>
     */
    public function entities(): Sequence
    {
        return $this->entities;
    }

    /**
     * @return Sequence<Optional>
     */
    public function optionals(): Sequence
    {
        return $this->optionals;
    }

    /**
     * @return Sequence<Collection>
     */
    public function collections(): Sequence
    {
        return $this->collections;
    }

    /**
     * @param ReflectionProperty<T> $property
     *
     * @return Maybe<Identity<T>|Property<T, mixed>|Entity|Optional|Collection>
     */
    private function parse(ReflectionProperty $property, Types $types): Maybe
    {
        return $this
            ->parseId($property)
            ->otherwise(fn() => $this->parseProperty($this->class, $property, $types))
            ->otherwise(fn() => $this->parseOptional($property, $types))
            ->otherwise(fn() => $this->parseCollection($property, $types))
            ->otherwise(fn() => $this->parseEntity($property, $types));
    }

    /**
     * @param ReflectionProperty<T> $property
     *
     * @return Maybe<Identity<T>>
     */
    private function parseId(ReflectionProperty $property): Maybe
    {
        return Maybe::just($property)
            ->filter(static fn($property) => $property->name() === 'id')
            ->filter(static fn($property) => $property->type()->toString() === Id::class)
            ->map(fn($property) => Identity::of($property->name(), $this->class));
    }

    /**
     * @template A of object
     *
     * @param class-string<A> $class
     * @param ReflectionProperty<A> $property
     *
     * @return Maybe<Property<A, mixed>>
     */
    private function parseProperty(
        string $class,
        ReflectionProperty $property,
        Types $types,
    ): Maybe {
        return Maybe::just($property)
            ->exclude(static fn($property) => $property->name() === 'id')
            ->flatMap(static fn($property) => $types(
                $property->type()->type(),
                $property
                    ->attributes()
                    ->find(static fn($attribute) => $attribute->class() === Contains::class)
                    ->map(static fn($attribute) => $attribute->instance())
                    ->keep(Instance::of(Contains::class))
                    ->match(
                        static fn($contains) => $contains,
                        static fn() => null,
                    ),
            ))
            ->map(static fn($type) => Property::of(
                $class,
                $property->name(),
                $type,
            ));
    }

    /**
     * @param ReflectionProperty<T> $property
     *
     * @return Maybe<Entity>
     */
    private function parseEntity(ReflectionProperty $property, Types $types): Maybe
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidArgument
         */
        return Maybe::just($property)
            ->exclude(static fn($property) => $property->name() === 'id')
            ->filter(static fn($property) => $property->type()->type() instanceof ClassName)
            ->map(fn($property) => Entity::of(
                $property->type()->toString(),
                $property->name(),
                Sequence::of(
                    ...ReflectionClass::of($property->type()->toString())
                        ->properties()
                        ->toList(),
                )
                    ->flatMap(
                        fn($innerProperty) => $this
                            ->parseProperty(
                                $property->type()->toString(),
                                $innerProperty,
                                $types,
                            )
                            ->toSequence(),
                    ),
            ));
    }

    /**
     * @param ReflectionProperty<T> $property
     *
     * @return Maybe<Optional>
     */
    private function parseOptional(ReflectionProperty $property, Types $types): Maybe
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidArgument
         */
        return Maybe::just($property)
            ->exclude(static fn($property) => $property->name() === 'id')
            ->filter(static fn($property) => $property->type()->type()->accepts(ClassName::of(Maybe::class)))
            ->flatMap(
                fn($property) => $property
                    ->attributes()
                    ->find(static fn($attribute) => $attribute->class() === Contains::class)
                    ->map(static fn($attribute) => $attribute->instance())
                    ->keep(Instance::of(Contains::class))
                    ->map(fn($contains) => Optional::of(
                        $contains->type()->toString(),
                        $property->name(),
                        Sequence::of(
                            ...ReflectionClass::of($contains->type()->toString())
                                ->properties()
                                ->toList(),
                        )
                            ->flatMap(
                                fn($innerProperty) => $this
                                    ->parseProperty(
                                        $property->type()->toString(),
                                        $innerProperty,
                                        $types,
                                    )
                                    ->toSequence(),
                            ),
                    )),
            );
    }

    /**
     * @param ReflectionProperty<T> $property
     *
     * @return Maybe<Collection>
     */
    private function parseCollection(ReflectionProperty $property, Types $types): Maybe
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress InvalidArgument
         */
        return Maybe::just($property)
            ->exclude(static fn($property) => $property->name() === 'id')
            ->filter(static fn($property) => $property->type()->type()->accepts(ClassName::of(Set::class)))
            ->flatMap(
                fn($property) => $property
                    ->attributes()
                    ->find(static fn($attribute) => $attribute->class() === Contains::class)
                    ->map(static fn($attribute) => $attribute->instance())
                    ->keep(Instance::of(Contains::class))
                    ->map(fn($contains) => Collection::of(
                        $contains->type()->toString(),
                        $property->name(),
                        Sequence::of(
                            ...ReflectionClass::of($contains->type()->toString())
                                ->properties()
                                ->toList(),
                        )
                            ->flatMap(
                                fn($innerProperty) => $this
                                    ->parseProperty(
                                        $property->type()->toString(),
                                        $innerProperty,
                                        $types,
                                    )
                                    ->toSequence(),
                            ),
                    )),
            );
    }
}
