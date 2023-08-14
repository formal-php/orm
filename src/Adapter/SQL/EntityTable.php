<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate\Entity as Definition,
    Raw\Aggregate\Id,
    Raw\Aggregate\Property,
};
use Formal\AccessLayer\{
    Table,
    Table\Column,
    Query,
    Query\Update,
    Query\Select\Join,
    Row,
};
use Innmind\Immutable\{
    Set,
    Maybe,
};

/**
 * @psalm-immutable
 * @template T of object
 */
final class EntityTable
{
    /** @var Definition<T> */
    private Definition $definition;
    private Table\Name\Aliased $main;
    private Table\Name\Aliased $name;
    /** @var Set<Column\Name\Aliased> */
    private Set $columns;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(
        Definition $definition,
        Table\Name\Aliased $main,
    ) {
        $this->definition = $definition;
        $this->main = $main;
        $this->name = Table\Name::of($main->name()->toString().'_'.$definition->name())->as($definition->name());
        $this->columns = $definition
            ->properties()
            ->map(
                fn($property) => Column\Name::of($property->name())
                    ->in($this->name)
                    ->as($definition->name().'_'.$property->name()),
            );
    }

    /**
     * @psalm-pure
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(
        Definition $definition,
        Table\Name\Aliased $main,
    ): self {
        return new self($definition, $main);
    }

    public function primaryKey(): Table\Column
    {
        return Table\Column::of(
            Table\Column\Name::of('id'),
            Table\Column\Type::varchar(36)->comment('UUID'),
        );
    }

    public function foreignKey(): Table\Column
    {
        return Table\Column::of(
            Table\Column\Name::of($this->definition->name()),
            Table\Column\Type::varchar(36)->comment('UUID'),
        );
    }

    /**
     * @return Set<Column>
     */
    public function columnsDefinition(MapType $mapType): Set
    {
        return $this
            ->definition
            ->properties()
            ->map(static fn($property) => Table\Column::of(
                Table\Column\Name::of($property->name()),
                $mapType($property->type()),
            ));
    }

    public function name(): Table\Name\Aliased
    {
        return $this->name;
    }

    /**
     * @return Set<Column\Name\Aliased>
     */
    public function columns(): Set
    {
        return $this->columns;
    }

    /**
     * @param non-empty-string $uuid
     * @param Set<Property> $properties
     */
    public function insert(string $uuid, Set $properties): Query
    {
        $table = $this->name->name();

        return Query\Insert::into(
            $table,
            new Row(
                new Row\Value(
                    Column\Name::of('id')->in($table),
                    $uuid,
                ),
                ...$properties
                    ->map(static fn($property) => new Row\Value(
                        Column\Name::of($property->name())->in($table),
                        $property->value(),
                    ))
                    ->toList(),
            ),
        );
    }

    /**
     * @param Set<Property> $properties
     *
     * @return Maybe<Query>
     */
    public function update(Id $id, Set $properties): Maybe
    {
        return Maybe::just($properties)
            ->filter(static fn($properties) => !$properties->empty())
            ->map(
                fn($properties) => Update::set(
                    $this->name,
                    new Row(
                        ...$properties
                            ->map(fn($property) => new Row\Value(
                                Column\Name::of($property->name())->in($this->name),
                                $property->value(),
                            ))
                            ->toList(),
                    ),
                )->join(Join::left($this->main)->on(
                    Column\Name::of('id')->in($this->name),
                    Column\Name::of($this->definition->name())->in($this->main),
                )),
            );
    }
}
