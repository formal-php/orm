<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Innmind\Immutable\Set;

/**
 * @template T of object
 */
final class Entity
{
    /** @var class-string<T> */
    private string $class;
    /** @var non-empty-string */
    private string $name;
    /** @var Set<Property<T, mixed>> */
    private Set $properties;

    /**
     * @param class-string<T> $class
     * @param non-empty-string $name
     * @param Set<Property<T, mixed>> $properties
     */
    private function __construct(
        string $class,
        string $name,
        Set $properties,
    ) {
        $this->class = $class;
        $this->name = $name;
        $this->properties = $properties;
    }

    /**
     * @template A of object
     *
     * @param class-string<A> $class
     * @param non-empty-string $name
     * @param Set<Property<A, mixed>> $properties
     *
     * @return self<A>
     */
    public static function required(
        string $class,
        string $name,
        Set $properties,
    ): self {
        return new self($class, $name, $properties);
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
     * @return Set<Property<T, mixed>>
     */
    public function properties(): Set
    {
        return $this->properties;
    }
}
