<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
    Specification\Property,
    Specification\Entity,
};
use Formal\AccessLayer\{
    Table,
    Table\Column,
    Query,
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
    /** @var Map<non-empty-string, EntityTable> */
    private Map $entities;
    /** @var Map<non-empty-string, OptionalTable> */
    private Map $optionals;

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
            ->columns(Column\Name::of($definition->id()->property()));
        // No need for this query to be lazy as the result is directly collapsed
        // to a boolean
        $this->count = Select::from($this->name)->count('count');
        $this->entities = $entities;
        $this->optionals = Map::of(
            ...$definition
                ->optionals()
                ->map(fn($entity) => [
                    $entity->name(),
                    OptionalTable::of($entity, $this->name),
                ])
                ->toList(),
        );
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
        return Query\Insert::into(
            $this->name->name(),
            new Row(
                new Row\Value(
                    Column\Name::of($this->definition->id()->property()),
                    $uuid,
                ),
                ...$properties
                    ->map(static fn($property) => new Row\Value(
                        Column\Name::of($property->name()),
                        $property->value(),
                    ))
                    ->toList(),
                ...$entities
                    ->map(static fn($name, $value) => new Row\Value(
                        Column\Name::of($name),
                        $value,
                    ))
                    ->values()
                    ->toList(),
                ...$optionals
                    ->map(static fn($name, $value) => new Row\Value(
                        Column\Name::of($name),
                        $value,
                    ))
                    ->values()
                    ->toList(),
            ),
        );
    }

    public function where(Specification $specification): Specification
    {
        if ($specification instanceof Not) {
            return $this->where($specification->specification());
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
     * @param non-empty-string $name
     *
     * @return Maybe<EntityTable>
     */
    public function entity(string $name): Maybe
    {
        return $this->entities->get($name);
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
}
