<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\Definition\Aggregates;
use Formal\AccessLayer\{
    Query,
    Query\Constraint\ForeignKey,
};
use Innmind\Immutable\Sequence;

final class ShowCreateTable
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
            ->map(fn($optional) => Query\CreateTable::named(
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
                fn($collection) => Query\CreateTable::named(
                    $collection->name()->name(),
                    $collection->primaryKey(),
                    $collection->foreignKey(),
                    ...$collection
                        ->columnsDefinition($this->mapType)
                        ->toList(),
                )
                    ->primaryKey($collection->primaryKey()->name())
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

        $main = Query\CreateTable::named(
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
        return new self($aggregates);
    }
}
