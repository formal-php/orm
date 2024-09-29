<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate\Collection as Definition,
    Raw\Aggregate\Id,
    Raw\Aggregate\Collection\Entity,
    Specification\Property as PropertySpecification,
};
use Formal\AccessLayer\{
    Table,
    Table\Column,
    Query,
    Query\Select,
    Query\Delete,
    Row,
};
use Innmind\Specification\Sign;
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Set,
    Sequence,
};

/**
 * @psalm-immutable
 * @template T of object
 */
final class CollectionTable
{
    /** @var Definition<T> */
    private Definition $definition;
    private Table\Name\Aliased $name;
    /** @var Sequence<Column\Name\Aliased> */
    private Sequence $columns;
    private Column\Name\Namespaced $id;
    private Select $select;

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
        $this->id = Column\Name::of('aggregateId')->in($this->name);
        $this->select = Select::from($this->name)->columns(
            $this->id,
            ...$this->columns->toList(),
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

    public function foreignKey(): Column
    {
        return Column::of(
            Column\Name::of('aggregateId'),
            Column\Type::uuid()->comment('UUID'),
        );
    }

    /**
     * @return Sequence<Column>
     */
    public function columnsDefinition(MapType $mapType): Sequence
    {
        return $this
            ->definition
            ->properties()
            ->map(static fn($property) => Column::of(
                Column\Name::of($property->name()),
                $mapType($property->type()),
            ));
    }

    public function name(): Table\Name\Aliased
    {
        return $this->name;
    }

    /**
     * @return Sequence<Column\Name\Aliased>
     */
    public function columns(): Sequence
    {
        return $this->columns;
    }

    /**
     * @internal
     */
    public function select(Id $id): Query
    {
        return $this->select->where(PropertySpecification::of(
            \sprintf(
                '%s.%s',
                $this->name->alias(),
                $this->id->column()->toString(),
            ),
            Sign::equality,
            $id->value(),
        ));
    }

    /**
     * @internal
     *
     * @param Set<Entity> $collection
     *
     * @return Sequence<Query>
     */
    public function insert(Id $id, Set $collection): Sequence
    {
        $table = $this->name->name();

        return $collection
            ->unsorted()
            ->map(static fn($entity) => Query\Insert::into(
                $table,
                Row::new(
                    Row\Value::of(
                        Column\Name::of('aggregateId'),
                        $id->value(),
                    ),
                    ...$entity
                        ->properties()
                        ->map(static fn($property) => Row\Value::of(
                            Column\Name::of($property->name()),
                            $property->value(),
                        ))
                        ->toList(),
                ),
            ));
    }

    /**
     * @internal
     *
     * @param Set<Entity> $entities
     *
     * @return Sequence<Query>
     */
    public function update(
        Id $id,
        Set $entities,
    ): Sequence {
        return Sequence::of(
            Delete::from($this->name)->where(PropertySpecification::of(
                \sprintf(
                    '%s.%s',
                    $this->name->alias(),
                    $this->id->column()->toString(),
                ),
                Sign::equality,
                $id->value(),
            )),
            ...$this
                ->insert($id, $entities)
                ->toList(),
        );
    }

    public function where(Specification $specification): Query
    {
        return Select::from($this->name)
            ->columns($this->id)
            ->where($specification);
    }
}
