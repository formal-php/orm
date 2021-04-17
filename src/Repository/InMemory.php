<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Repository,
    Id,
    Definition\Aggregate,
};
use Innmind\Reflection\ReflectionObject;
use Innmind\Immutable\{
    Maybe,
    Set,
    Map,
};
use Innmind\Specification\Specification;

/**
 * @template V of object
 * @implements Repository<V>
 */
final class InMemory implements Repository
{
    /** @var class-string<V> */
    private string $class;
    private Aggregate $definition;
    /** @var Map<string, V> */
    private Map $aggregates;
    /** @var Map<string, V> */
    private Map $toAdd;
    /** @var Set<string> */
    private Set $toRemove;
    /** @var callable(): bool */
    private $allowMutation;

    /**
     * @param class-string<V> $class
     * @param callable(): bool $allowMutation
     */
    public function __construct(string $class, callable $allowMutation)
    {
        $this->class = $class;
        $this->definition = Aggregate::of($class);
        /** @var Map<string, V> */
        $this->aggregates = Map::of('string', $class);
        /** @var Map<string, V> */
        $this->toAdd = Map::of('string', $class);
        $this->toRemove = Set::strings();
        $this->allowMutation = $allowMutation;
    }

    public function get(Id $id): Maybe
    {
        $reference = $id->toString();

        if ($this->toRemove->contains($reference)) {
            /** @var Maybe<V> */
            return Maybe::nothing();
        }

        if ($this->toAdd->contains($reference)) {
            return Maybe::just($this->toAdd->get($reference));
        }

        if ($this->aggregates->contains($reference)) {
            return Maybe::just($this->aggregates->get($reference));
        }

        /** @var Maybe<V> */
        return Maybe::nothing();
    }

    public function add(object $aggregate): void
    {
        $this->assertMutable();

        $property = $this->definition->id()->property();
        /** @var Id<V> */
        $id = ReflectionObject::of($aggregate)
            ->extract($property)
            ->get($property);

        $this->toAdd = ($this->toAdd)($id->toString(), $aggregate);
    }

    public function remove(Id $id): void
    {
        $this->assertMutable();

        $this->toRemove = ($this->toRemove)($id->toString());
        $this->toAdd = $this->toAdd->remove($id->toString());
    }

    public function all(): Set
    {
        return $this
            ->aggregates
            ->merge($this->toAdd)
            ->filter(fn($id) => !$this->toRemove->contains($id))
            ->values()
            ->toSetOf($this->class);
    }

    public function matching(Specification $specification): Set
    {
        return Set::of($this->class);
    }

    /**
     * @internal
     */
    public function commit(): void
    {
        $this->aggregates = $this
            ->aggregates
            ->merge($this->toAdd)
            ->filter(fn($id) => !$this->toRemove->contains($id));
        $this->toAdd = $this->toAdd->clear();
        $this->toRemove = $this->toRemove->clear();
    }

    /**
     * @internal
     */
    public function rollback(): void
    {
        $this->toAdd = $this->toAdd->clear();
        $this->toRemove = $this->toRemove->clear();
    }

    /**
     * @throws \LogicException
     */
    private function assertMutable(): void
    {
        if (!($this->allowMutation)()) {
            throw new \LogicException('Trying to mutate the repository outside of a transaction');
        }
    }
}
