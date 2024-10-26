<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
};
use Formal\AccessLayer\{
    Connection,
    Row,
    Table\Column,
};
use Innmind\Immutable\{
    Maybe,
    Set,
    Sequence,
    Str,
};

/**
 * @internal
 * @template T of object
 */
final class Decode
{
    /** @var MainTable<T> */
    private MainTable $mainTable;
    private Connection $connection;
    /** @var non-empty-string */
    private string $entityPrefix;
    /** @var non-empty-string */
    private string $id;

    /**
     * @param Definition<T> $definition
     * @param MainTable<T> $mainTable
     */
    private function __construct(
        Definition $definition,
        MainTable $mainTable,
        Connection $connection,
    ) {
        $this->mainTable = $mainTable;
        $this->connection = $connection;
        $this->entityPrefix = $mainTable->name()->alias().'_';
        $this->id = $this->entityPrefix.$definition->id()->property();
    }

    /**
     * @return callable(Row): Maybe<Aggregate>
     */
    public function __invoke(Aggregate\Id $id = null): callable
    {
        $idName = $this->id;
        /** @psalm-suppress MixedArgument */
        $id = match ($id) {
            null => static fn(Row $row) => $row
                ->column($idName)
                ->filter(\is_string(...))
                ->map(static fn($id) => Aggregate\Id::of(
                    $idName,
                    $id,
                )),
            default => static fn(Row $row) => Maybe::just($id),
        };

        $entityPrefix = $this->entityPrefix;
        $mainTable = $this->mainTable;
        $connection = $this->connection;

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress ArgumentTypeCoercion
         */
        return static fn(Row $row) => $id($row)->map(static fn($id) => Aggregate::of(
            $id,
            $row
                ->values()
                ->filter(static fn($value) => Str::of($value->column()->toString())->startsWith($entityPrefix))
                ->map(static fn($value) => Aggregate\Property::of(
                    Str::of($value->column()->toString())->drop(7)->toString(),
                    $value->value(),
                )),
            $mainTable
                ->entities()
                ->map(
                    static fn($entity) => Aggregate\Entity::of(
                        $entity->name()->alias(),
                        self::properties($row, $entity->columns()),
                    ),
                ),
            $mainTable
                ->optionals()
                ->map(static fn($optional) => Aggregate\Optional::of(
                    $optional->name()->alias(),
                    Maybe::defer(
                        static fn() => $connection($optional->select($id))
                            ->first()
                            ->map(static fn($row) => self::properties(
                                $row,
                                $optional->columns(),
                            )),
                    ),
                )),
            $mainTable
                ->collections()
                ->map(static fn($collection) => Aggregate\Collection::of(
                    $collection->name()->alias(),
                    Set::defer(
                        (static function() use ($connection, $collection, $id) {
                            // Wrapping this call in a deferred Set allows to
                            // not immediately make the request but only when
                            // asked.
                            // The memoize is here to make sure the user can't
                            // work with a partially loaded collection
                            $entities = $connection($collection->select($id))
                                ->map(static fn($row) => Aggregate\Collection\Entity::of(
                                    self::properties(
                                        $row,
                                        $collection->columns(),
                                    ),
                                ))
                                ->toList();

                            foreach ($entities as $entity) {
                                yield $entity;
                            }
                        })(),
                    ),
                )),
        ));
    }

    /**
     * @internal
     * @template A of object
     *
     * @param Definition<A> $definition
     * @param MainTable<A> $mainTable
     *
     * @return self<A>
     */
    public static function of(
        Definition $definition,
        MainTable $mainTable,
        Connection $connection,
    ): self {
        return new self($definition, $mainTable, $connection);
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Column\Name\Aliased> $columns
     *
     * @return Sequence<Aggregate\Property>
     */
    private static function properties(Row $row, Sequence $columns): Sequence
    {
        /** @psalm-suppress MixedArgument Due to the access-layer type */
        return $columns->flatMap(
            static fn($column) => $row
                ->column($column->alias())
                ->map(static fn($value) => Aggregate\Property::of(
                    match (true) {
                        $column->name() instanceof Column\Name => $column->name()->toString(),
                        $column->name() instanceof Column\Name\Namespaced => $column->name()->column()->toString(),
                    },
                    $value,
                ))
                ->toSequence(),
        );
    }
}
