<?php
declare(strict_types = 1);

namespace Formal\ORM\SQL;

use Formal\ORM\{
    Definition\Aggregate,
    Id,
};
use Formal\AccessLayer\{
    Connection,
    Query,
    Table\Name,
    Row,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Maybe,
    Set,
    Sequence,
};

/**
 * @template T of object
 */
final class Table
{
    /** @var Aggregate<T> */
    private Aggregate $aggregate;
    private Connection $connection;
    private Table\Normalize $normalize;
    private Table\Denormalize $denormalize;

    /**
     * @param Aggregate<T> $aggregate
     */
    public function __construct(
        Aggregate $aggregate,
        Types $types,
        Connection $connection,
    ) {
        $this->aggregate = $aggregate;
        $this->connection = $connection;
        $this->normalize = new Table\Normalize($aggregate, $types);
        $this->denormalize = new Table\Denormalize($aggregate, $types);
    }

    /**
     * @param Id<T> $id
     *
     * @return Maybe<T>
     */
    public function get(Id $id): Maybe
    {
        $select = $this->select()->where($this->match($id));
        /** @var Sequence<T> */
        $aggregates = ($this->connection)($select)->mapTo(
            $this->aggregate->class(),
            fn($row) => ($this->denormalize)($row),
        );

        if (!$aggregates->empty()) {
            return Maybe::just($aggregates->first());
        }

        /** @var Maybe<T> */
        return Maybe::nothing();
    }

    /**
     * @param T $aggregate
     */
    public function insert(object $aggregate): void
    {
        ($this->connection)(new Query\Insert(
            $this->table(),
            Row::of(($this->normalize)($aggregate)),
        ));
    }

    /**
     * @param Id<T> $id
     * @param T $aggregate
     */
    public function update(Id $id, object $aggregate): void
    {
        // todo only update what changed
        $update = new Query\Update(
            $this->table(),
            Row::of(($this->normalize)($aggregate)),
        );
        ($this->connection)($update->where($this->match($id)));
    }

    /**
     * @param Id<T> $id
     */
    public function delete(Id $id): void
    {
        ($this->connection)(
            (new Query\Delete($this->table()))->where($this->match($id)),
        );
    }

    /**
     * @return Set<T>
     */
    public function all(): Set
    {
        return ($this->connection)($this->select())
            ->mapTo(
                $this->aggregate->class(),
                fn($row) => ($this->denormalize)($row),
            )
            ->toSetOf($this->aggregate->class());
    }

    private function table(): Name
    {
        return new Name($this->aggregate->name());
    }

    private function select(): Query\Select
    {
        return new Query\Select($this->table());
    }

    private function match(Id $id): Specification
    {
        return new MatchId(
            $this->aggregate->id()->property(),
            $id,
        );
    }
}
