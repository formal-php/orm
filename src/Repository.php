<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Maybe,
    Sequence,
};

/**
 * @template T of object
 */
final class Repository
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
    public static function of(string $class): self
    {
        return new self($class);
    }

    /**
     * @param Id<T> $id
     *
     * @return Maybe<T>
     */
    public function get(Id $id): Maybe
    {
        /** @var Maybe<T> */
        return Maybe::nothing();
    }

    /**
     * @param Id<T> $id
     */
    public function contains(Id $id): bool
    {
        return false;
    }

    /**
     * @param T $aggregate
     */
    public function put(object $aggregate): void
    {
    }

    /**
     * @param Id<T> $id
     */
    public function delete(Id $id): void
    {
    }

    /**
     * @return Matching<T>
     */
    public function matching(Specification $specification): Matching
    {
        return Matching::of($this->class, $specification);
    }

    /**
     * @return 0|positive-int
     */
    public function size(?Specification $specification): int
    {
        return 0;
    }

    /**
     * @return Sequence<T>
     */
    public function all(): Sequence
    {
        return Sequence::of();
    }
}
