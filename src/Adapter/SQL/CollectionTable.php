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
    Maybe,
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
    /** @var Set<Column\Name\Aliased> */
    private Set $columns;
    private Column\Name\Namespaced $id;
    private Column\Name\Namespaced $reference;
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
        $this->id = Column\Name::of('id')->in($this->name);
        $this->reference = Column\Name::of('entityReference')->in($this->name);
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

    public function primaryKey(): Table\Column
    {
        return Table\Column::of(
            Table\Column\Name::of('id'),
            Table\Column\Type::varchar(36)->comment('UUID'),
        );
    }

    public function referenceColumn(): Table\Column
    {
        return Table\Column::of(
            Table\Column\Name::of('entityReference'),
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
     * @return Maybe<Query>
     */
    public function insert(Id $id, Set $collection): Maybe
    {
        $table = $this->name->name();

        /** @psalm-suppress InvalidScalarArgument Psalm doesn't understand the !empty() */
        return Maybe::just($collection)
            ->filter(static fn($collection) => !$collection->empty())
            ->map(
                static fn($collection) => Query\Insert::into(
                    $table,
                    ...$collection
                        ->map(
                            static fn($entity) => new Row(
                                new Row\Value(
                                    Column\Name::of('id')->in($table),
                                    $id->value(),
                                ),
                                new Row\Value(
                                    Column\Name::of('entityReference')->in($table),
                                    $entity->reference()->toString(),
                                ),
                                ...$entity
                                    ->properties()
                                    ->map(static fn($property) => new Row\Value(
                                        Column\Name::of($property->name())->in($table),
                                        $property->value(),
                                    ))
                                    ->toList(),
                            ),
                        )
                        ->toList(),
                ),
            );
    }

    /**
     * @internal
     *
     * @param Set<Entity> $newEntities
     * @param Set<Entity\Reference> $unmodifiedEntities
     *
     * @return Sequence<Query>
     */
    public function update(
        Id $id,
        Set $newEntities,
        Set $unmodifiedEntities,
    ): Sequence {
        $specification = PropertySpecification::of(
            \sprintf(
                '%s.%s',
                $this->name->alias(),
                $this->id->column()->toString(),
            ),
            Sign::equality,
            $id->value(),
        );

        if (!$unmodifiedEntities->empty()) {
            $specification = $specification->and(
                PropertySpecification::of(
                    \sprintf(
                        '%s.%s',
                        $this->name->alias(),
                        $this->reference->column()->toString(),
                    ),
                    Sign::in,
                    $unmodifiedEntities
                        ->map(static fn($reference) => $reference->toString())
                        ->toList(),
                )->not(),
            );
        }

        return Sequence::of(
            Delete::from($this->name)->where($specification),
            ...$this
                ->insert($id, $newEntities)
                ->toSequence()
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
