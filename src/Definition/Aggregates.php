<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

final class Aggregates
{
    private Types $types;

    private function __construct(Types $types)
    {
        $this->types = $types;
    }

    public static function of(Types $types): self
    {
        return new self($types);
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
        return Aggregate::of($this->types, $class);
    }
}
