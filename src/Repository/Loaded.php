<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Definition\Aggregate,
    Id,
};
use Innmind\Immutable\Maybe;

/**
 * @internal
 * @template T of object
 */
final class Loaded
{
    /** @var Aggregate<T> */
    private Aggregate $definition;
    /** @var \WeakMap<Id<T>, T> */
    private \WeakMap $loaded;

    /**
     * @param Aggregate<T> $definition
     */
    private function __construct(Aggregate $definition)
    {
        $this->definition = $definition;
        /** @var \WeakMap<Id<T>, T> */
        $this->loaded = new \WeakMap;
    }

    /**
     * @template A of object
     *
     * @param Aggregate<A> $definition
     *
     * @return self<A>
     */
    public static function of(Aggregate $definition): self
    {
        return new self($definition);
    }

    /**
     * @param T $aggregate
     *
     * @return T
     */
    public function add(object $aggregate): object
    {
        $id = $this->definition->id()->extract($aggregate);
        $this->loaded[$id] = $aggregate;

        return $aggregate;
    }

    /**
     * @param Id<T> $id
     *
     * @return callable(T): T
     */
    public function put(Id $id): callable
    {
        return function(object $aggregate) use ($id) {
            $this->loaded[$id] = $aggregate;

            return $aggregate;
        };
    }

    /**
     * @param Id<T> $id
     *
     * @return Maybe<T>
     */
    public function get(Id $id): Maybe
    {
        return Maybe::of($this->loaded[$id] ?? null);
    }

    /**
     * @param Id<T> $id
     */
    public function remove(Id $id): void
    {
        $this->loaded->offsetUnset($id);
    }
}
