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
    /** @var Update<T> */
    private Update $update;
    /** @var non-empty-string */
    private string $idColumn;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Connection $connection, Definition $definition)
    {
        $this->connection = $connection;
        $this->definition = $definition;
        $this->mainTable = MainTable::of($definition);
        $this->decode = Decode::of($definition, $this->mainTable, $connection);
        $this->encode = Encode::of($definition, $this->mainTable);
        $this->update = Update::of($this->mainTable);
        $this->idColumn = \sprintf(
            '%s.%s',
            $this->mainTable->name()->alias(),
            $definition->id()->property(),
        );
    }

    /**
     * @internal
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
            ->where(Property::of($this->idColumn, Sign::equality, $id->value()));

        return ($this->connection)($select)
            ->first()
            ->flatMap(($this->decode)($id));
    }

    public function contains(Aggregate\Id $id): bool
    {
        $select = $this
            ->mainTable
            ->contains()
            ->where(Property::of($this->idColumn, Sign::equality, $id->value()));

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
        $_ = ($this->update)($data)->foreach($this->connection);
    }

    public function remove(Aggregate\Id $id): void
    {
        $_ = ($this->connection)(
            $this
                ->mainTable
                ->delete()
                ->where(Property::of($this->idColumn, Sign::equality, $id->value())),
        );
    }

    public function fetch(
        ?Specification $specification,
        null|Sort\Property|Sort\Entity $sort,
        ?int $drop,
        ?int $take,
    ): Sequence {
        $select = $this->mainTable->select($specification);

        if ($sort) {
            $column = match (true) {
                $sort instanceof Sort\Property => Table\Column\Name::of($sort->name())->in(
                    $this->mainTable->name(),
                ),
                $sort instanceof Sort\Entity => Table\Column\Name::of($sort->property()->name())->in(
                    Table\Name::of($sort->name()),
                ),
            };
            $select = $select->orderBy(
                $column,
                match ($sort->direction()) {
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
        $count = $this->mainTable->count($specification);

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

    public function any(Specification $specification = null): bool
    {
        return $this->size($specification) !== 0;
    }

    public function none(Specification $specification = null): bool
    {
        return $this->size($specification) === 0;
    }
}
