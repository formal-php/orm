<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate\Entity as Definition,
    Raw\Aggregate\Id,
    Raw\Aggregate\Property,
    Specification,
};
use Formal\AccessLayer\{
    Table,
    Table\Column,
    Query,
    Query\Update,
    Row,
};
use Innmind\Specification\Sign;
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
     * @internal
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
            Table\Column\Name::of('aggregateId'),
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
     * @internal
     *
     * @param Set<Property> $properties
     */
    public function insert(Id $id, Set $properties): Query
    {
        $table = $this->name->name();

        return Query\Insert::into(
            $table,
            new Row(
                new Row\Value(
                    Column\Name::of('aggregateId')->in($table),
                    $id->value(),
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
     * @internal
     *
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
                )->where(Specification\Property::of(
                    'aggregateId',
                    Sign::equality,
                    $id->value(),
                )),
            );
    }
}
