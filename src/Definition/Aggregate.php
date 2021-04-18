<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Innmind\Reflection\{
    ReflectionClass,
    ReflectionObject,
    ExtractionStrategy,
    InjectionStrategy,
    Instanciator,
};
use Innmind\Immutable\{
    Set,
    Maybe,
    Map,
    Str,
    Exception\NoElementMatchingPredicateFound,
};
use function Innmind\Immutable\unwrap;

/**
 * @template T of object
 */
final class Aggregate
{
    /** @var class-string<T> */
    private string $class;
    /** @var Set<string> */
    private Set $exclude;
    /** @var Maybe<string> */
    private Maybe $name;

    /**
     * @param class-string<T> $class
     */
    private function __construct(string $class)
    {
        $this->class = $class;
        $this->exclude = Set::strings();
        /** @var Maybe<string> */
        $this->name = Maybe::nothing();
    }

    /**
     * @template V of object
     *
     * @param class-string<V> $class
     *
     * @return self<V>
     */
    public static function of(string $class): self
    {
        return new self($class);
    }

    /**
     * @return class-string<T>
     */
    public function class(): string
    {
        return $this->class;
    }

    public function exclude(string $property): self
    {
        $self = clone $this;
        $self->exclude = ($this->exclude)($property);

        return $self;
    }

    /**
     * Name to use for the underlying storage
     *
     * @return self<T>
     */
    public function referenceAs(string $name): self
    {
        $self = clone $this;
        $self->name = Maybe::just($name);

        return $self;
    }

    /**
     * @throws \LogicException When no id defined in the aggregate
     *
     * @return Id<T>
     */
    public function id(): Id
    {
        try {
            /** @var Id<T> */
            return new Id(
                $this
                    ->properties()
                    ->find(static fn($property) => $property->isId())
                    ->name(),
            );
        } catch (NoElementMatchingPredicateFound $e) {
            throw new \LogicException("No id property defined for '{$this->class}'");
        }
    }

    /**
     * @return Set<Property>
     */
    public function properties(): Set
    {
        return ReflectionClass::of($this->class)
            ->properties()
            ->filter(fn($property) => !$this->exclude->contains($property))
            ->mapTo(
                Property::class,
                fn($property) => Property::of($this->class, $property),
            );
    }

    public function name(): string
    {
        return $this->name->match(
            static fn($name) => $name,
            fn() => Str::of($this->class)
                ->split('\\')
                ->last()
                ->toLower()
                ->toString(),
        );
    }

    /**
     * Extract all the values of the object that need to be persisted
     *
     * @param T $aggregate
     *
     * @return Map<string, mixed>
     */
    public function normalize(object $aggregate): Map
    {
        $properties = $this->properties()->mapTo(
            'string',
            static fn($property) => $property->name(),
        );

        return ReflectionObject::of(
            $aggregate,
            null,
            null,
            new ExtractionStrategy\ReflectionStrategy,
        )
            ->extract(...unwrap($properties));
    }

    /**
     * Create a new object out of the given values
     *
     * @param Map<string, mixed> $values
     *
     * @return T
     */
    public function denormalize(Map $values): object
    {
        $reflection = ReflectionClass::of(
            $this->class,
            $values,
            new InjectionStrategy\ReflectionStrategy,
            new Instanciator\ConstructorLessInstanciator,
        );

        return $reflection->build();
    }
}
