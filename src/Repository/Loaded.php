<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Definition\Aggregate,
    Id,
};
use Innmind\Immutable\{
    Maybe,
    Map,
};

/**
 * @internal
 * @template T of object
 */
final class Loaded
{
    private Active $repositories;
    /** @var Aggregate<T> */
    private Aggregate $definition;
    /** @var \WeakMap<Id<T>, Map<non-empty-string, mixed>> */
    private \WeakMap $loaded;

    /**
     * @param Aggregate<T> $definition
     */
    private function __construct(Active $repositories, Aggregate $definition)
    {
        $this->repositories = $repositories;
        $this->definition = $definition;
        /** @var \WeakMap<Id<T>, Map<non-empty-string, mixed>> */
        $this->loaded = new \WeakMap;
    }

    /**
     * @template A of object
     *
     * @param Aggregate<A> $definition
     *
     * @return self<A>
     */
    public static function of(Active $repositories, Aggregate $definition): self
    {
        return new self($repositories, $definition);
    }

    /**
     * @param Denormalized<T> $denormalized
     *
     * @return Denormalized<T>
     */
    public function add(Denormalized $denormalized): Denormalized
    {
        $this->loaded[$denormalized->id()] = $denormalized->properties();
        $this->repositories->active($this->definition->class(), $denormalized->id());

        return $denormalized;
    }

    /**
     * @param Id<T> $id
     *
     * @return Maybe<Denormalized<T>>
     */
    public function get(Id $id): Maybe
    {
        return Maybe::of($this->loaded[$id] ?? null)->map(
            static fn($properties) => Denormalized::of($id, $properties),
        );
    }

    /**
     * @param Id<T> $id
     */
    public function remove(Id $id): void
    {
        $this->loaded->offsetUnset($id);
        $this->repositories->forget($id);
    }
}
