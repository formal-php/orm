<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Repository,
    Id,
    Definition\Aggregate,
    SQL\Types,
    SQL\MatchId,
    SQL\Table,
    SQL\Table\Normalize,
    SQL\Table\Denormalize,
};
use Formal\AccessLayer\Connection;
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Maybe,
    Set,
};

/**
 * @template V of object
 * @implements Repository<V>
 */
final class SQL implements Repository
{
    /** @var Aggregate<V> */
    private Aggregate $aggregate;
    /** @var Table<V> */
    private Table $table;
    /** @var callable(Id<V>): Maybe<V> */
    private $lookup;
    /** @var callable(Id<V>, V): void */
    private $cache;
    /** @var callable(Id<V>): void */
    private $invalidate;
    /** @var callable(): bool */
    private $allowMutation;

    /**
     * @param Aggregate<V> $aggregate
     * @param callable(Id<V>): Maybe<V> $lookup
     * @param callable(Id<V>, V): void $cache
     * @param callable(Id<V>): void $invalidate
     * @param callable(): bool $allowMutation
     */
    public function __construct(
        Aggregate $aggregate,
        Connection $connection,
        Types $types,
        callable $lookup,
        callable $cache,
        callable $invalidate,
        callable $allowMutation,
    ) {
        $this->aggregate = $aggregate;
        $this->table = new Table($aggregate, $types, $connection);
        $this->lookup = $lookup;
        $this->cache = $cache;
        $this->invalidate = $invalidate;
        $this->allowMutation = $allowMutation;
    }

    public function get(Id $id): Maybe
    {
        return ($this->lookup)($id)
            ->otherwise(fn() => $this->table->get($id))
            ->map(function($aggregate) {
                /**
                 * @psalm-suppress InvalidArgument Don't know why it loses the template here
                 * @var V $aggregate
                 */
                ($this->cache)(
                    $this->extractId($aggregate),
                    $aggregate,
                );

                return $aggregate;
            });
    }

    public function add(object $aggregate): void
    {
        $this->assertMutable();

        $id = $this->extractId($aggregate);
        /** @psalm-suppress UnusedMethodCall */
        ($this->lookup)($id)->match(
            fn() => $this->table->update($id, $aggregate),
            fn() => $this->table->insert($aggregate),
        );

        ($this->cache)($id, $aggregate);
    }

    public function remove(Id $id): void
    {
        $this->assertMutable();

        $this->table->delete($id);
        ($this->invalidate)($id);
    }

    public function all(): Set
    {
        return $this->table->all();
    }

    public function matching(Specification $specification): Set
    {
        // todo
        return Set::of($this->aggregate->class());
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

    /**
     * @param V $aggregate
     *
     * @return Id<V>
     */
    private function extractId(object $aggregate): Id
    {
        return $this->aggregate->id()->extract($aggregate);
    }
}
