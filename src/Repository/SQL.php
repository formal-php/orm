<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Repository,
    Id,
    Definition\Aggregate,
    SQL\Types,
    SQL\MatchId,
    SQL\Table\Normalize,
    SQL\Table\Denormalize,
};
use Formal\AccessLayer\{
    Connection,
    Row,
    Query,
    Table,
};
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
    /** @var class-string<V> */
    private string $class;
    /** @var Aggregate<V> */
    private Aggregate $aggregate;
    private Connection $connection;
    private Types $types;
    /** @var Normalize<V> */
    private Normalize $normalize;
    /** @var Denormalize<V> */
    private Denormalize $denormalize;
    /** @var callable(Id<V>): Maybe<V> */
    private $lookup;
    /** @var callable(Id<V>, V): void */
    private $cache;
    /** @var callable(Id<V>): void */
    private $invalidate;
    /** @var callable(): bool */
    private $allowMutation;

    /**
     * @param class-string<V> $class
     * @param Aggregate<V> $aggregate
     * @param callable(Id<V>): Maybe<V> $lookup
     * @param callable(Id<V>, V): void $cache
     * @param callable(Id<V>): void $invalidate
     * @param callable(): bool $allowMutation
     */
    public function __construct(
        string $class,
        Aggregate $aggregate,
        Connection $connection,
        Types $types,
        callable $lookup,
        callable $cache,
        callable $invalidate,
        callable $allowMutation
    ) {
        $this->class = $class;
        $this->aggregate = $aggregate;
        $this->connection = $connection;
        $this->types = $types;
        $this->normalize = new Normalize($aggregate, $types);
        $this->denormalize = new Denormalize($aggregate, $types);
        $this->lookup = $lookup;
        $this->cache = $cache;
        $this->invalidate = $invalidate;
        $this->allowMutation = $allowMutation;
    }

    public function get(Id $id): Maybe
    {
        return ($this->lookup)($id)
            ->otherwise(fn() => $this->lookup($id));
    }

    public function add(object $aggregate): void
    {
        $this->assertMutable();

        $id = $this->extractId($aggregate);
        /** @psalm-suppress UnusedMethodCall */
        ($this->lookup)($id)->match(
            fn() => $this->update($id, $aggregate),
            fn() => $this->insert($aggregate),
        );

        ($this->cache)($id, $aggregate);
    }

    public function remove(Id $id): void
    {
        $this->assertMutable();

        ($this->connection)(
            (new Query\Delete(new Table\Name($this->aggregate->name())))
                ->where($this->match($id)),
        );
        ($this->invalidate)($id);
    }

    public function all(): Set
    {
        return ($this->connection)($this->select())
            ->mapTo(
                $this->class,
                fn($row) => ($this->denormalize)($row),
            )
            ->toSetOf($this->class);
    }

    public function matching(Specification $specification): Set
    {
        return Set::of($this->class);
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

    private function select(): Query\Select
    {
        return new Query\Select(new Table\Name($this->aggregate->name()));
    }

    private function match(Id $id): Specification
    {
        return new MatchId(
            $this->aggregate->id()->property(),
            $id,
        );
    }

    /**
     * @param Id<V> $id
     *
     * @return Maybe<V>
     */
    private function lookup(Id $id): Maybe
    {
        $select = $this->select()->where($this->match($id));
        $aggregates = ($this->connection)($select)
            ->mapTo($this->class, fn($row) => ($this->denormalize)($row));

        if (!$aggregates->empty()) {
            $aggregate = $aggregates->first();

            ($this->cache)(
                $this->extractId($aggregate),
                $aggregate,
            );

            return Maybe::just($aggregate);
        }

        /** @var Maybe<V> */
        return Maybe::nothing();
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

    /**
     * @param V $aggregate
     */
    private function insert(object $aggregate): void
    {
        ($this->connection)(new Query\Insert(
            new Table\Name($this->aggregate->name()),
            Row::of(($this->normalize)($aggregate)),
        ));
    }

    /**
     * @param Id<V> $id
     * @param V $aggregate
     */
    private function update(Id $id, object $aggregate): void
    {
        // todo only update what changed
        $update = new Query\Update(
            new Table\Name($this->aggregate->name()),
            Row::of(($this->normalize)($aggregate)),
        );
        ($this->connection)($update->where($this->match($id)));
    }
}
