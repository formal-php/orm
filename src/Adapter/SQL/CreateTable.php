<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\Definition\{
    Aggregates,
    Type,
};
use Formal\AccessLayer\{
    Query,
    Query\Constraint\ForeignKey,
    Table,
};
use Innmind\Immutable\Sequence;

final class CreateTable
{
    private Aggregates $aggregates;
    private MapType $mapType;

    private function __construct(Aggregates $aggregates)
    {
        $this->aggregates = $aggregates;
        $this->mapType = MapType::new();
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

        $entities = $mainTable
            ->entities()
            ->map(fn($entity) => Query\CreateTable::named(
                $entity->name()->name(),
                $entity->primaryKey(),
                ...$entity
                    ->columnsDefinition($this->mapType)
                    ->toList(),
            )->primaryKey($entity->primaryKey()->name()))
            ->toList();
        $optionals = $mainTable
            ->optionals()
            ->map(fn($optional) => Query\CreateTable::named(
                $optional->name()->name(),
                $optional->primaryKey(),
                ...$optional
                    ->columnsDefinition($this->mapType)
                    ->toList(),
            )->primaryKey($optional->primaryKey()->name()))
            ->toList();

        $collections = $mainTable
            ->collections()
            ->map(fn($collection) => Query\CreateTable::named(
                $collection->name()->name(),
                $collection->primaryKey(),
                ...$collection
                    ->columnsDefinition($this->mapType)
                    ->toList(),
            )->constraint(
                ForeignKey::of(
                    $collection->primaryKey()->name(),
                    $mainTable->name()->name(),
                    $mainTable->primaryKey()->name(),
                )->onDeleteCascade(),
            ))
            ->toList();

        $main = Query\CreateTable::named(
            $mainTable->name()->name(),
            $mainTable->primaryKey(),
            ...$mainTable
                ->columnsDefinition($this->mapType)
                ->toList(),
        )->primaryKey($mainTable->primaryKey()->name());

        $main = $mainTable
            ->entities()
            ->reduce(
                $main,
                static fn(Query\CreateTable $main, $entity) => $main->foreignKey(
                    $entity->foreignKey()->name(),
                    $entity->name()->name(),
                    $entity->primaryKey()->name(),
                ),
            );
        $main = $mainTable
            ->optionals()
            ->reduce(
                $main,
                static fn(Query\CreateTable $main, $optional) => $main->constraint(
                    ForeignKey::of(
                        $optional->foreignKey()->name(),
                        $optional->name()->name(),
                        $optional->primaryKey()->name(),
                    )->onDeleteSetNull(),
                ),
            );

        return Sequence::of(...[
            ...$entities,
            ...$optionals,
            $main,
            ...$collections,
        ]);
    }

    public static function of(Aggregates $aggregates): self
    {
        return new self($aggregates);
    }
}
