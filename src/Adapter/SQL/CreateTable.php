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

    private function __construct(Aggregates $aggregates)
    {
        $this->aggregates = $aggregates;
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
            ->map(static fn($entity) => Query\CreateTable::named(
                $entity->name()->name(),
                $entity->primaryKey(),
                ...$entity
                    ->definition()
                    ->properties()
                    ->map(static fn($property) => Table\Column::of(
                        Table\Column\Name::of($property->name()),
                        self::determineType($property->type()),
                    ))
                    ->toList(),
            )->primaryKey($entity->primaryKey()->name()))
            ->toList();
        $optionals = $mainTable
            ->optionals()
            ->map(static fn($optional) => Query\CreateTable::named(
                $optional->name()->name(),
                $optional->primaryKey(),
                ...$optional
                    ->definition()
                    ->properties()
                    ->map(static fn($property) => Table\Column::of(
                        Table\Column\Name::of($property->name()),
                        self::determineType($property->type()),
                    ))
                    ->toList(),
            )->primaryKey($optional->primaryKey()->name()))
            ->toList();

        $collections = $mainTable
            ->collections()
            ->map(static fn($collection) => Query\CreateTable::named(
                $collection->name()->name(),
                $collection->primaryKey(),
                ...$collection
                    ->definition()
                    ->properties()
                    ->map(static fn($property) => Table\Column::of(
                        Table\Column\Name::of($property->name()),
                        self::determineType($property->type()),
                    ))
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
            ...$definition
                ->properties()
                ->map(static fn($property) => Table\Column::of(
                    Table\Column\Name::of($property->name()),
                    self::determineType($property->type()),
                ))
                ->toList(),
            ...$mainTable
                ->entities()
                ->map(static fn($entity) => $entity->foreignKey())
                ->toList(),
            ...$mainTable
                ->optionals()
                ->map(static fn($optional) => $optional->foreignKey())
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

    private static function determineType(Type $type): Table\Column\Type
    {
        return match (true) {
            $type instanceof Type\NullableType,
            $type instanceof Type\MaybeType => self::determineType($type->inner())->nullable(),
            $type instanceof Type\BoolType => Table\Column\Type::tinyint(1)
                ->comment('Boolean'),
            $type instanceof Type\IdType => Table\Column\Type::varchar(36)
                ->comment('UUID'),
            $type instanceof Type\IntType => Table\Column\Type::bigint()
                ->comment('TODO Adjust the size depending on your use case'),
            $type instanceof Type\PointInTimeType => Table\Column\Type::varchar(32)
                ->comment('Date with timezone down to the microsecond'),
            default => Table\Column\Type::longtext()
                ->comment('TODO adjust the type depending on your use case'),
        };
    }
}
