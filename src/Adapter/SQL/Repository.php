<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Adapter\Repository as RepositoryInterface,
    Definition\Aggregate as Definition,
    Raw\Aggregate,
    Raw\Diff,
    Specification\Property,
    Sort,
};
use Formal\AccessLayer\{
    Connection,
    Query\Select\Direction,
    Table,
};
use Innmind\Specification\{
    Specification,
    Sign,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

/**
 * @internal
 * @template T of object
 * @implements RepositoryInterface<T>
 */
final class Repository implements RepositoryInterface
{
    private Connection $connection;
    /** @var Definition<T> */
    private Definition $definition;
    /** @var MainTable<T> */
    private MainTable $mainTable;
    /** @var Decode<T> */
    private Decode $decode;
    /** @var Encode<T> */
    private Encode $encode;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Connection $connection, Definition $definition)
    {
        $this->connection = $connection;
        $this->definition = $definition;
        $this->mainTable = MainTable::of($definition);
        $this->decode = Decode::of($definition);
        $this->encode = Encode::of($definition, $this->mainTable);
    }

    /**
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(Connection $connection, Definition $definition): self
    {
        return new self($connection, $definition);
    }

    public function get(Aggregate\Id $id): Maybe
    {
        $select = $this
            ->mainTable
            ->select()
            ->where(Property::of('entity.id', Sign::equality, $id->value()));

        return ($this->connection)($select)
            ->first()
            ->flatMap(($this->decode)($id));
    }

    public function contains(Aggregate\Id $id): bool
    {
        $select = $this
            ->mainTable
            ->contains()
            ->where(Property::of('id', Sign::equality, $id->value()));

        return ($this->connection)($select)
            ->first()
            ->match(
                static fn() => true,
                static fn() => false,
            );
    }

    public function add(Aggregate $data): void
    {
        $_ = ($this->encode)($data)->foreach($this->connection);
    }

    public function update(Diff $data): void
    {
    }

    public function remove(Aggregate\Id $id): void
    {
        $_ = ($this->connection)(
            $this
                ->mainTable
                ->delete()
                ->where(Property::of('user.id', Sign::equality, $id->value())),
        );
    }

    public function matching(
        Specification $specification,
        ?array $sort,
        ?int $drop,
        ?int $take,
    ): Sequence {
        $select = $this
            ->mainTable
            ->select()
            ->where(
                $this->mainTable->where($specification),
            );

        if (\is_array($sort)) {
            [$column, $direction] = $sort;
            $select = $select->orderBy(
                Table\Column\Name::of($column)->in($this->mainTable->name()),
                match ($direction) {
                    Sort::asc => Direction::asc,
                    Sort::desc => Direction::desc,
                },
            );
        }

        if (\is_int($take)) {
            $select = $select->limit($take, $drop);
        }

        $decode = ($this->decode)();
        $aggregates = ($this->connection)($select)
            ->flatMap(static fn($row) => $decode($row)->toSequence());

        if (\is_int($drop) && \is_null($take)) {
            // SQL doesn't allow to create an offset without a limit so instead
            // of creating an arbitrary huge limit we apply the offset in PHP.
            // This is not optimal but it is better than returning an invalid
            // number of aggregates
            $aggregates = $aggregates->drop($drop);
        }

        return $aggregates;
    }

    public function size(Specification $specification = null): int
    {
        $count = $this->mainTable->count();
        $count = match ($specification) {
            null => $count,
            default => $count->where(
                $this->mainTable->where($specification),
            ),
        };

        /** @var 0|positive-int SQL count() should never return a negative value */
        return ($this->connection)($count)
            ->first()
            ->flatMap(static fn($row) => $row->column('count'))
            ->filter(\is_numeric(...))
            ->map(static fn($count) => (int) $count)
            ->match(
                static fn($count) => $count,
                static fn() => 0,
            );
    }

    public function all(): Sequence
    {
        $decode = ($this->decode)();

        return ($this->connection)($this->mainTable->select())
            ->flatMap(static fn($row) => $decode($row)->toSequence());
    }
}
