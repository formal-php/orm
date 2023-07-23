<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Formal\ORM\Definition\Aggregate\Entity\Kind;
use Innmind\Immutable\Set;

/**
 * @template T of object
 */
final class Entity
{
    /** @var class-string<T> */
    private string $class;
    /** @var non-empty-string */
    private string $property;
    private Kind $kind;
    /** @var Set<Property<T, mixed>> */
    private Set $properties;

    /**
     * @param class-string<T> $class
     * @param non-empty-string $property
     * @param Set<Property<T, mixed>> $properties
     */
    private function __construct(
        string $class,
        string $property,
        Kind $kind,
        Set $properties,
    ) {
        $this->class = $class;
        $this->property = $property;
        $this->kind = $kind;
        $this->properties = $properties;
    }

    /**
     * @template A of object
     *
     * @param class-string<A> $class
     * @param non-empty-string $property
     * @param Set<Property<A, mixed>> $properties
     *
     * @return self<A>
     */
    public static function required(
        string $class,
        string $property,
        Set $properties,
    ): self {
        return new self($class, $property, Kind::required, $properties);
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
    public function property(): string
    {
        return $this->property;
    }

    /**
     * @return Set<Property<T, mixed>>
     */
    public function properties(): Set
    {
        return $this->properties;
    }
}
