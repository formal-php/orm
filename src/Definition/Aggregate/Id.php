<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

/**
 * @template T of object
 */
final class Id
{
    /** @var non-empty-string */
    private string $property;
    /** @var class-string<T> */
    private string $class;

    /**
     * @param non-empty-string $property
     * @param class-string<T> $class
     */
    private function __construct(string $property, string $class)
    {
        $this->property = $property;
        $this->class = $class;
    }

    /**
     * @template A
     *
     * @param non-empty-string $property
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function of(string $property, string $class): self
    {
        return new self($property, $class);
    }
}
