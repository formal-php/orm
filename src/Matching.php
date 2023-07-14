<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Innmind\Specification\Specification;
use Innmind\Immutable\Sequence;

/**
 * @template T of object
 */
final class Matching
{
    /** @var class-string<T> */
    private string $class;

    /**
     * @param class-string<T> $class
     */
    private function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * @template A of object
     *
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function of(string $class, Specification $specification): self
    {
        return new self($class);
    }

    /**
     * @psalm-mutation-free
     *
     * @param positive-int $size
     *
     * @return self<T>
     */
    public function take(int $size): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     *
     * @param positive-int $size
     *
     * @return self<T>
     */
    public function drop(int $size): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     *
     * @param non-empty-string $property
     *
     * @return self<T>
     */
    public function sort(string $property, Sort $direction): self
    {
        return $this;
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(self<T>): self<T> $map
     *
     * @return self<T>
     */
    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this);
    }

    /**
     * @return Sequence<T>
     */
    public function fetch(): Sequence
    {
        return Sequence::of();
    }
}
