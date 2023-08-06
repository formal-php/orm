<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate as Definition,
};
use Formal\AccessLayer\{
    Table,
    Table\Column,
    Query\Select,
    Query\Select\Join,
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

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->name = Table\Name::of($definition->name())->as('entity');
        $select = $definition
            ->entities()
            ->reduce(
                Select::onDemand($this->name),
                fn(Select $select, $entity) => $select->join(
                    Join::left(Table\Name::of($definition->name().'_'.$entity->name())->as($entity->name()))->on(
                        Column\Name::of($entity->name())->in($this->name),
                        Column\Name::of('id')->in(Table\Name::of($entity->name())),
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
                ...$definition
                    ->entities()
                    ->flatMap(
                        static fn($entity) => $entity
                            ->properties()
                            ->map(
                                static fn($property) => Column\Name::of($property->name())
                                    ->in(Table\Name::of($entity->name()))
                                    ->as($entity->name().'_'.$property->name()),
                            ),
                    )
                    ->toList(),
            );
        // No need for this query to be lazy as the result is directly collapsed
        // to a boolean
        $this->contains = Select::from($this->name)
            ->columns(Column\Name::of($definition->id()->property()));
        $this->count = Select::onDemand($this->name)->columns(
            Column\Name::of('count(1)')->as('count'),
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
}
