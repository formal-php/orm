<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Formal\ORM\{
    Id,
    Raw,
    Definition\Aggregate\Parsing,
};
use Innmind\Reflection\{
    ReflectionClass,
    Instanciate,
};
use Innmind\Immutable\{
    Str,
    Set,
    Map,
    Monoid\Concat,
    Predicate\Instance,
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

    /**
     * @param class-string<T> $class
     * @param Aggregate\Identity<T> $id
     * @param Set<Aggregate\Property<T, mixed>> $properties
     * @param Set<Aggregate\Entity> $entities
     */
    private function __construct(
        string $class,
        Aggregate\Identity $id,
        Set $properties,
        Set $entities,
    ) {
        $this->class = $class;
        $this->id = $id;
        $this->properties = $properties;
        $this->entities = $entities;
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
     * @param T $then
     * @param T $now
     */
    public function diff(object $then, object $now): Raw\Diff
    {
        /** @var Id<T> */
        $id = $this->id()->extract($then);

        return Raw\Diff::of(
            $this->id()->normalize($id),
            $this
                ->properties
                ->flatMap(
                    static fn($property) => $property
                        ->diff($then, $now)
                        ->toSequence()
                        ->toSet(),
                ),
            $this
                ->entities
                ->flatMap(
                    static fn($entity) => $entity
                        ->diff($then, $now)
                        ->toSequence()
                        ->toSet(),
                ),
        );
    }
}
