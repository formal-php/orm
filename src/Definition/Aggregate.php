<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Formal\ORM\Definition\Aggregate\Parsing;
use Innmind\Reflection\ReflectionClass;
use Innmind\Immutable\{
    Str,
    Sequence,
    Monoid\Concat,
};

/**
 * @psalm-immutable
 * @template T of object
 */
final class Aggregate
{
    /** @var class-string<T> */
    private string $class;
    /** @var Aggregate\Identity<T> */
    private Aggregate\Identity $id;
    /** @var Sequence<Aggregate\Property<T, mixed>> */
    private Sequence $properties;
    /** @var Sequence<Aggregate\Entity> */
    private Sequence $entities;
    /** @var Sequence<Aggregate\Optional> */
    private Sequence $optionals;
    /** @var Sequence<Aggregate\Collection> */
    private Sequence $collections;

    /**
     * @param class-string<T> $class
     * @param Aggregate\Identity<T> $id
     * @param Sequence<Aggregate\Property<T, mixed>> $properties
     * @param Sequence<Aggregate\Entity> $entities
     * @param Sequence<Aggregate\Optional> $optionals
     * @param Sequence<Aggregate\Collection> $collections
     */
    private function __construct(
        string $class,
        Aggregate\Identity $id,
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
     * @template A
     *
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function of(Types $types, string $class): self
    {
        /** @var Parsing<A> Type lost due to the reduce */
        $parsed = ReflectionClass::of($class)
            ->properties()
            ->reduce(
                Parsing::of($class),
                static fn(Parsing $parsing, $property) => $parsing->with($property, $types),
            );

        return $parsed->id()->match(
            static fn($id) => new self(
                $class,
                $id,
                $parsed->properties(),
                $parsed->entities(),
                $parsed->optionals(),
                $parsed->collections(),
            ),
            static fn() => throw new \LogicException('A property named "id" must be typed Id<self>'),
        );
    }

    /**
     * @return class-string<T>
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        /** @var non-empty-string */
        return Str::of($this->class)
            ->split('\\')
            ->takeEnd(1)
            ->fold(new Concat)
            ->toLower()
            ->toString();
    }

    /**
     * @return Aggregate\Identity<T>
     */
    public function id(): Aggregate\Identity
    {
        return $this->id;
    }

    /**
     * @return Sequence<Aggregate\Property<T, mixed>>
     */
    public function properties(): Sequence
    {
        return $this->properties;
    }

    /**
     * @return Sequence<Aggregate\Entity>
     */
    public function entities(): Sequence
    {
        return $this->entities;
    }

    /**
     * @return Sequence<Aggregate\Optional>
     */
    public function optionals(): Sequence
    {
        return $this->optionals;
    }

    /**
     * @return Sequence<Aggregate\Collection>
     */
    public function collections(): Sequence
    {
        return $this->collections;
    }
}
