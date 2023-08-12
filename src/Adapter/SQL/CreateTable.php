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
        $id = Table\Column::of(
            Table\Column\Name::of($definition->id()->property()),
            Table\Column\Type::varchar(36),
        );

        $entities = $definition
            ->entities()
            ->map(static fn($entity) => Query\CreateTable::named(
                Table\Name::of($definition->name().'_'.$entity->name()),
                Table\Column::of(
                    Table\Column\Name::of('id'),
                    Table\Column\Type::varchar(36)->comment('UUID'),
                ),
                ...$entity
                    ->properties()
                    ->map(static fn($property) => Table\Column::of(
                        Table\Column\Name::of($property->name()),
                        self::determineType($property->type()),
                    ))
                    ->toList(),
            )->primaryKey(Table\Column\Name::of('id')))
            ->toList();
        $optionals = $definition
            ->optionals()
            ->map(static fn($optional) => Query\CreateTable::named(
                Table\Name::of($definition->name().'_'.$optional->name()),
                Table\Column::of(
                    Table\Column\Name::of('id'),
                    Table\Column\Type::varchar(36)->comment('UUID'),
                ),
                ...$optional
                    ->properties()
                    ->map(static fn($property) => Table\Column::of(
                        Table\Column\Name::of($property->name()),
                        self::determineType($property->type()),
                    ))
                    ->toList(),
            )->primaryKey(Table\Column\Name::of('id')))
            ->toList();

        $collections = $definition
            ->collections()
            ->map(static fn($collection) => Query\CreateTable::named(
                Table\Name::of($definition->name().'_'.$collection->name()),
                Table\Column::of(
                    Table\Column\Name::of('id'),
                    Table\Column\Type::varchar(36)->comment('UUID'),
                ),
                ...$collection
                    ->properties()
                    ->map(static fn($property) => Table\Column::of(
                        Table\Column\Name::of($property->name()),
                        self::determineType($property->type()),
                    ))
                    ->toList(),
            )->constraint(
                ForeignKey::of(
                    Table\Column\Name::of('id'),
                    Table\Name::of($definition->name()),
                    $id->name(),
                )->onDeleteCascade(),
            ))
            ->toList();

        $main = Query\CreateTable::named(
            Table\Name::of($definition->name()),
            $id,
            ...$definition
                ->properties()
                ->map(static fn($property) => Table\Column::of(
                    Table\Column\Name::of($property->name()),
                    self::determineType($property->type()),
                ))
                ->toList(),
            ...$definition
                ->entities()
                ->map(static fn($entity) => Table\Column::of(
                    Table\Column\Name::of($entity->name()),
                    Table\Column\Type::varchar(36)->comment('UUID'),
                ))
                ->toList(),
            ...$definition
                ->optionals()
                ->map(static fn($entity) => Table\Column::of(
                    Table\Column\Name::of($entity->name()),
                    Table\Column\Type::varchar(36)->nullable()->comment('UUID'),
                ))
                ->toList(),
        )->primaryKey($id->name());

        $main = $definition
            ->entities()
            ->reduce(
                $main,
                static fn(Query\CreateTable $main, $entity) => $main->foreignKey(
                    Table\Column\Name::of($entity->name()),
                    Table\Name::of($definition->name().'_'.$entity->name()),
                    Table\Column\Name::of('id'),
                ),
            );
        $main = $definition
            ->optionals()
            ->reduce(
                $main,
                static fn(Query\CreateTable $main, $optional) => $main->constraint(
                    ForeignKey::of(
                        Table\Column\Name::of($optional->name()),
                        Table\Name::of($definition->name().'_'.$optional->name()),
                        Table\Column\Name::of('id'),
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
