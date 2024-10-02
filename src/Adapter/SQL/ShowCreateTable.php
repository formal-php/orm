<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\Definition\Aggregates;
use Formal\AccessLayer\{
    Query,
    Query\Constraint\ForeignKey,
    Table\Name,
    Table\Column,
};
use Innmind\Immutable\Sequence;

final class ShowCreateTable
{
    private Aggregates $aggregates;
    private MapType $mapType;
    private bool $ifNotExists;

    /**
     * @psalm-mutation-free
     */
    private function __construct(
        Aggregates $aggregates,
        MapType $mapType,
        bool $ifNotExists,
    ) {
        $this->aggregates = $aggregates;
        $this->mapType = $mapType;
        $this->ifNotExists = $ifNotExists;
    }

    /**
     * @param class-string $class
     *
     * @return Sequence<Query>
     */
    public function __invoke(string $class): Sequence
    {
        $definition = $this->aggregates->get($class);
        $mainTable = MainTable::of($definition);
        /** @psalm-suppress NamedArgumentNotAllowed */
        $create = match ($this->ifNotExists) {
            true => static fn(Name $name, Column $first, Column ...$rest) => Query\CreateTable::ifNotExists(
                $name, $first, ...$rest,
            ),
            false => static fn(Name $name, Column $first, Column ...$rest) => Query\CreateTable::named(
                $name, $first, ...$rest,
            ),
        };

        $entities = $mainTable
            ->entities()
            ->map(fn($entity) => $create(
                $entity->name()->name(),
                $entity->primaryKey(),
                ...$entity
                    ->columnsDefinition($this->mapType)
                    ->toList(),
            )
                ->constraint(
                    ForeignKey::of(
                        $entity->primaryKey()->name(),
                        $mainTable->name()->name(),
                        $mainTable->primaryKey()->name(),
                    )
                        ->onDeleteCascade()
                        ->named($entity->name()->name()->toString()),
                )
                ->unique($entity->primaryKey()->name()),
            )
            ->toList();
        $optionals = $mainTable
            ->optionals()
            ->map(fn($optional) => $create(
                $optional->name()->name(),
                $optional->primaryKey(),
                ...$optional
                    ->columnsDefinition($this->mapType)
                    ->toList(),
            )
                ->constraint(
                    ForeignKey::of(
                        $optional->primaryKey()->name(),
                        $mainTable->name()->name(),
                        $mainTable->primaryKey()->name(),
                    )
                        ->onDeleteCascade()
                        ->named($optional->name()->name()->toString()),
                )
                ->unique($optional->primaryKey()->name()),
            )
            ->toList();

        $collections = $mainTable
            ->collections()
            ->map(
                fn($collection) => $create(
                    $collection->name()->name(),
                    $collection->foreignKey(),
                    ...$collection
                        ->columnsDefinition($this->mapType)
                        ->toList(),
                )
                    ->constraint(
                        ForeignKey::of(
                            $collection->foreignKey()->name(),
                            $mainTable->name()->name(),
                            $mainTable->primaryKey()->name(),
                        )
                            ->onDeleteCascade()
                            ->named($collection->name()->name()->toString()),
                    ),
            )
            ->toList();

        $main = $create(
            $mainTable->name()->name(),
            $mainTable->primaryKey(),
            ...$mainTable
                ->columnsDefinition($this->mapType)
                ->toList(),
        )->primaryKey($mainTable->primaryKey()->name());

        return Sequence::of(...[
            $main,
            ...$entities,
            ...$optionals,
            ...$collections,
        ]);
    }

    public static function of(Aggregates $aggregates): self
    {
        return new self(
            $aggregates,
            MapType::new(),
            false,
        );
    }

    /**
     * This will add the "IF NOT EXIST" to the sql queries
     *
     * @psalm-mutation-free
     */
    public function ifNotExists(): self
    {
        return new self(
            $this->aggregates,
            $this->mapType,
            true,
        );
    }
}
