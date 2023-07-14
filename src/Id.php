<?php
declare(strict_types = 1);

namespace Formal\ORM;

/**
 * @template T of object
 */
final class Id
{
    /**
     * @param class-string<T> $class
     */
    private function __construct(string $class)
    {
    }

    /**
     * @template A of object
     *
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function new(string $class): self
    {
        return new self($class);
    }
}
