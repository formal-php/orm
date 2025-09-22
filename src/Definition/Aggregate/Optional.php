<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @template T of object
 */
final class Optional
{
    /**
     * @param class-string<T> $class
     * @param non-empty-string $name
     * @param Sequence<Property<T, mixed>> $properties
     */
    private function __construct(
        private string $class,
        private string $name,
        private Sequence $properties,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     * @template A of object
     *
     * @param class-string<A> $class
     * @param non-empty-string $name
     * @param Sequence<Property<A, mixed>> $properties
     *
     * @return self<A>
     */
    public static function of(
        string $class,
        string $name,
        Sequence $properties,
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
     * @return Sequence<Property<T, mixed>>
     */
    public function properties(): Sequence
    {
        return $this->properties;
    }
}
