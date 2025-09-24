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
    /**
     * @param class-string<T> $class
     * @param non-empty-string $name
     * @param Aggregate\Identity<T> $id
     * @param Sequence<Aggregate\Property<T, mixed>> $properties
     * @param Sequence<Aggregate\Entity> $entities
     * @param Sequence<Aggregate\Optional> $optionals
     * @param Sequence<Aggregate\Collection> $collections
     */
    private function __construct(
        private string $class,
        private string $name,
        private Aggregate\Identity $id,
        private Sequence $properties,
        private Sequence $entities,
        private Sequence $optionals,
        private Sequence $collections,
    ) {
    }

    /**
     * @internal
     * @template A
     *
     * @param callable(class-string): non-empty-string $mapName
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function of(
        Types $types,
        ?callable $mapName,
        string $class,
    ): self {
        $mapName ??= static fn(string $class): string =>  Str::of($class)
            ->split('\\')
            ->takeEnd(1)
            ->fold(new Concat)
            ->toLower()
            ->toString();
        /** @var Parsing<A> Type lost due to the reduce */
        $parsed = ReflectionClass::of($class)
            ->properties()
            ->reduce(
                Parsing::of($class),
                static fn(Parsing $parsing, $property) => $parsing->with($property, $types),
            );
        /** @var non-empty-string */
        $name = $mapName($class);

        return $parsed->id()->match(
            static fn($id) => new self(
                $class,
                $name,
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
        return $this->name;
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
