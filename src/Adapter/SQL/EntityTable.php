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
    Sequence,
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
    /** @var Sequence<Column\Name\Aliased> */
    private Sequence $columns;

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

    public function primaryKey(): Column
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
     *
     * @param Sequence<Property> $properties
     */
    public function insert(Id $id, Sequence $properties): Query
    {
        return Query\Insert::into(
            $this->name->name(),
            Row::new(
                Row\Value::of(
                    Column\Name::of('aggregateId'),
                    $id->value(),
                ),
                ...$properties
                    ->map(static fn($property) => Row\Value::of(
                        Column\Name::of($property->name()),
                        $property->value(),
                    ))
                    ->toList(),
            ),
        );
    }

    /**
     * @internal
     *
     * @param Sequence<Property> $properties
     *
     * @return Maybe<Query>
     */
    public function update(Id $id, Sequence $properties): Maybe
    {
        $name = $this->name;

        return Maybe::just($properties)
            ->filter(static fn($properties) => !$properties->empty())
            ->map(
                static fn($properties) => Update::set(
                    $name,
                    Row::new(
                        ...$properties
                            ->map(static fn($property) => Row\Value::of(
                                Column\Name::of($property->name()),
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
