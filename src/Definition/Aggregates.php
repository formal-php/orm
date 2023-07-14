<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

final class Aggregates
{
    private function __construct()
    {
    }

    public static function of(): self
    {
        return new self;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return Aggregate<T>
     */
    public function get(string $class): Aggregate
    {
        return Aggregate::of($class);
    }
}
