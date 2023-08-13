<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
    Raw\Diff,
    Specification\Property,
    Specification\Entity,
};
use Formal\AccessLayer\{
    Table,
    Table\Column,
    Query,
    Query\Delete,
    Query\Update,
    Query\Select,
    Query\Select\Join,
    Row,
};
use Innmind\Specification\{
    Specification,
    Not,
    Comparator,
    Composite,
    Operator,
    Sign,
};
use Innmind\Immutable\{
    Map,
    Maybe,
    Set,
};

/**
 * @template T of object
 */
final class MainTable
{
    /** @var Definition<T> */
    private Definition $definition;
    private Table\Name\Aliased $name;
    private Select $select;
    private Select $contains;
    private Select $count;
    private Delete $delete;
    /** @var Map<non-empty-string, EntityTable> */
    private Map $entities;
    /** @var Map<non-empty-string, OptionalTable> */
    private Map $optionals;
    /** @var Map<non-empty-string, CollectionTable> */
    private Map $collections;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->name = Table\Name::of($definition->name())->as('entity');
        $entities = Map::of(
            ...$definition
                ->entities()
                ->map(fn($entity) => [
                    $entity->name(),
                    EntityTable::of($entity, $this->name),
                ])
                ->toList(),
        );
        $optionals = Map::of(
            ...$definition
                ->optionals()
                ->map(fn($entity) => [
                    $entity->name(),
                    OptionalTable::of($entity, $this->name, $definition->id()),
                ])
                ->toList(),
        );
        $collections = Map::of(
            ...$definition
                ->collections()
                ->map(fn($collection) => [
                    $collection->name(),
                    CollectionTable::of($collection, $this->name),
                ])
                ->toList(),
        );
        $select = $entities->reduce(
            Select::onDemand($this->name),
            fn(Select $select, $name, $table) => $select->join(
                Join::left($table->name())->on(
                    Column\Name::of($name)->in($this->name),
                    Column\Name::of('id')->in($table->name()),
                ),
            ),
        );
        $this->select = $select
            ->columns(
                Column\Name::of($definition->id()->property())
                    ->in($this->name)
                    ->as('entity_'.$definition->id()->property()),
                ...$definition
                    ->properties()
                    ->map(
                        fn($property) => Column\Name::of($property->name())
                            ->in($this->name)
                            ->as('entity_'.$property->name()),
                    )
                    ->toList(),
                ...$entities
                    ->values()
                    ->toSet()
                    ->flatMap(static fn($table) => $table->columns())
                    ->toList(),
            );
        // No need for this query to be lazy as the result is directly collapsed
        // to a boolean
        $this->contains = Select::from($this->name)
            ->columns(Column\Name::of($definition->id()->property())->in($this->name));
        // No need for this query to be lazy as the result is directly collapsed
        // to a boolean
        $this->count = Select::from($this->name)->count('count');
        $delete = $entities->reduce(
            Delete::from($this->name),
            fn(Delete $delete, $name, $table) => $delete->join(
                Join::left($table->name())->on(
                    Column\Name::of($name)->in($this->name),
                    Column\Name::of('id')->in($table->name()),
                ),
            ),
        );
        $this->delete = $optionals->reduce(
            $delete,
            fn(Delete $delete, $name, $table) => $delete->join(
                Join::left($table->name())->on(
                    Column\Name::of($name)->in($this->name),
                    Column\Name::of('id')->in($table->name()),
                ),
            ),
        );
        $this->entities = $entities;
        $this->optionals = $optionals;
        $this->collections = $collections;
    }

    /**
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(Definition $definition): self
    {
        return new self($definition);
    }

    public function primaryKey(): Table\Column
    {
        return Table\Column::of(
            Table\Column\Name::of($this->definition->id()->property()),
            Table\Column\Type::varchar(36)->comment('UUID'),
        );
    }

    public function name(): Table\Name\Aliased
    {
        return $this->name;
    }

    public function select(): Select
    {
        return $this->select;
    }

    public function contains(): Select
    {
        return $this->contains;
    }

    public function count(): Select
    {
        return $this->count;
    }

    /**
     * @param non-empty-string $uuid
     * @param Set<Aggregate\Property> $properties
     * @param Map<non-empty-string, non-empty-string> $entities
     * @param Map<non-empty-string, non-empty-string> $optionals
     */
    public function insert(
        string $uuid,
        Set $properties,
        Map $entities,
        Map $optionals,
    ): Query {
        $table = $this->name->name();

        return Query\Insert::into(
            $table,
            new Row(
                new Row\Value(
                    Column\Name::of($this->definition->id()->property())->in($table),
                    $uuid,
                ),
                ...$properties
                    ->map(static fn($property) => new Row\Value(
                        Column\Name::of($property->name())->in($table),
                        $property->value(),
                    ))
                    ->toList(),
                ...$entities
                    ->map(static fn($name, $value) => new Row\Value(
                        Column\Name::of($name)->in($table),
                        $value,
                    ))
                    ->values()
                    ->toList(),
                ...$optionals
                    ->map(static fn($name, $value) => new Row\Value(
                        Column\Name::of($name)->in($table),
                        $value,
                    ))
                    ->values()
                    ->toList(),
            ),
        );
    }

    /**
     * @return Maybe<Query>
     */
    public function update(Diff $data): Maybe
    {
        $table = $this->name->name();

        return Maybe::just($data->properties())
            ->filter(static fn($properties) => !$properties->empty())
            ->map(
                fn($properties) => Update::set(
                    $table,
                    new Row(
                        ...$properties
                            ->map(static fn($property) => new Row\Value(
                                Column\Name::of($property->name())->in($table),
                                $property->value(),
                            ))
                            ->toList(),
                    ),
                )->where(Property::of(
                    $this->definition->id()->property(),
                    Sign::equality,
                    $data->id()->value(),
                )),
            );
    }

    public function delete(): Delete
    {
        return $this->delete;
    }

    public function where(Specification $specification): Specification
    {
        if ($specification instanceof Not) {
            return $this->where($specification->specification())->not();
        }

        if ($specification instanceof Composite) {
            $left = $this->where($specification->left());
            $right = $this->where($specification->right());

            return match ($specification->operator()) {
                Operator::and => $left->and($right),
                Operator::or => $left->or($right),
            };
        }

        if ($specification instanceof Entity) {
            return Property::of(
                \sprintf(
                    '%s.%s',
                    $specification->entity(),
                    $specification->property(),
                ),
                $specification->sign(),
                $specification->value(),
            );
        }

        if (!($specification instanceof Property)) {
            $class = $specification::class;

            throw new \LogicException("Unsupported specification '$class'");
        }

        return Property::of(
            'entity.'.$specification->property(),
            $specification->sign(),
            $specification->value(),
        );
    }

    /**
     * @return Set<EntityTable>
     */
    public function entities(): Set
    {
        return $this->entities->values()->toSet();
    }

    /**
     * @param non-empty-string $name
     *
     * @return Maybe<EntityTable>
     */
    public function entity(string $name): Maybe
    {
        return $this->entities->get($name);
    }

    /**
     * @return Set<OptionalTable>
     */
    public function optionals(): Set
    {
        return $this->optionals->values()->toSet();
    }

    /**
     * @param non-empty-string $name
     *
     * @return Maybe<OptionalTable>
     */
    public function optional(string $name): Maybe
    {
        return $this->optionals->get($name);
    }

    /**
     * @return Set<CollectionTable>
     */
    public function collections(): Set
    {
        return $this->collections->values()->toSet();
    }

    /**
     * @param non-empty-string $name
     *
     * @return Maybe<CollectionTable>
     */
    public function collection(string $name): Maybe
    {
        return $this->collections->get($name);
    }
}
