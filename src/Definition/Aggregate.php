<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Formal\ORM\Definition\Aggregate\Parsing;
use Innmind\Reflection\ReflectionClass;
use Innmind\Immutable\{
    Str,
    Set,
    Monoid\Concat,
};

/**
 * @template T of object
 */
final class Aggregate
{
    /** @var class-string<T> */
    private string $class;
    /** @var Aggregate\Identity<T> */
    private Aggregate\Identity $id;
    /** @var Set<Aggregate\Property<T, mixed>> */
    private Set $properties;
    /** @var Set<Aggregate\Entity> */
    private Set $entities;
    /** @var Set<Aggregate\Optional> */
    private Set $optionals;
    /** @var Set<Aggregate\Collection> */
    private Set $collections;

    /**
     * @param class-string<T> $class
     * @param Aggregate\Identity<T> $id
     * @param Set<Aggregate\Property<T, mixed>> $properties
     * @param Set<Aggregate\Entity> $entities
     * @param Set<Aggregate\Optional> $optionals
     * @param Set<Aggregate\Collection> $collections
     */
    private function __construct(
        string $class,
        Aggregate\Identity $id,
        Set $properties,
        Set $entities,
        Set $optionals,
        Set $collections,
    ) {
        $this->class = $class;
        $this->id = $id;
        $this->properties = $properties;
        $this->entities = $entities;
        $this->optionals = $optionals;
        $this->collections = $collections;
    }

    /**
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
     * @return Set<Aggregate\Property<T, mixed>>
     */
    public function properties(): Set
    {
        return $this->properties;
    }

    /**
     * @return Set<Aggregate\Entity>
     */
    public function entities(): Set
    {
        return $this->entities;
    }

    /**
     * @return Set<Aggregate\Optional>
     */
    public function optionals(): Set
    {
        return $this->optionals;
    }

    /**
     * @return Set<Aggregate\Collection>
     */
    public function collections(): Set
    {
        return $this->collections;
    }
}
