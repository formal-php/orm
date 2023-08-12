<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate\Identity,
    Definition\Aggregate\Optional as Definition,
    Raw\Aggregate\Id,
    Raw\Aggregate\Property,
    Specification\Property as PropertySpecification,
};
use Formal\AccessLayer\{
    Table,
    Table\Column,
    Query,
    Query\Update,
    Query\Delete,
    Query\Select,
    Query\Select\Join,
    Row,
};
use Innmind\Specification\Sign;
use Innmind\Immutable\{
    Set,
    Maybe,
};

/**
 * @template T of object
 */
final class OptionalTable
{
    /** @var Definition<T> */
    private Definition $definition;
    private Table\Name\Aliased $main;
    private Identity $identity;
    private Table\Name\Aliased $name;
    /** @var Set<Column\Name\Aliased> */
    private Set $columns;
    private Select $select;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(
        Definition $definition,
        Table\Name\Aliased $main,
        Identity $identity,
    ) {
        $this->definition = $definition;
        $this->main = $main;
        $this->identity = $identity;
        $this->name = Table\Name::of($main->name()->toString().'_'.$definition->name())->as($definition->name());
        $this->columns = $definition
            ->properties()
            ->map(
                fn($property) => Column\Name::of($property->name())
                    ->in($this->name)
                    ->as($definition->name().'_'.$property->name()),
            );
        $this->select = Select::onDemand($this->name)
            ->join(
                Join::left($this->main)->on(
                    Column\Name::of($definition->name())->in($this->main),
                    Column\Name::of('id')->in($this->name),
                ),
            )
            ->columns(
                Column\Name::of('id')->in($this->name),
                ...$this->columns->toList(),
            );
    }

    /**
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(
        Definition $definition,
        Table\Name\Aliased $main,
        Identity $identity,
    ): self {
        return new self($definition, $main, $identity);
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

    public function select(Id $id): Select
    {
        return $this->select->where(PropertySpecification::of(
            \sprintf(
                '%s.%s',
                $this->main->alias(),
                $this->identity->property(),
            ),
            Sign::equality,
            $id->value(),
        ));
    }

    /**
     * @param non-empty-string $uuid
     * @param Set<Property> $properties
     */
    public function insert(string $uuid, Set $properties): Query
    {
        return Query\Insert::into(
            $this->name->name(),
            new Row(
                new Row\Value(
                    Column\Name::of('id'),
                    $uuid,
                ),
                ...$properties
                    ->map(static fn($property) => new Row\Value(
                        Column\Name::of($property->name()),
                        $property->value(),
                    ))
                    ->toList(),
            ),
        );
    }

    /**
     * @param Maybe<Set<Property>> $properties
     */
    public function update(Id $id, Maybe $properties): Query
    {
        // TODO handle the diff scenario : creating optional after aggregate creation
        return $properties->match(
            fn($properties) => Update::set(
                $this->name,
                new Row(
                    ...$properties
                        ->map(static fn($property) => new Row\Value(
                            Column\Name::of($property->name()),
                            $property->value(),
                        ))
                        ->toList(),
                ),
            )
                ->join(
                    Join::left($this->main)->on(
                        Column\Name::of('id')->in($this->name),
                        Column\Name::of($this->definition->name())->in($this->main),
                    ),
                )
                ->where(PropertySpecification::of(
                    \sprintf(
                        '%s.%s',
                        $this->main->alias(),
                        $this->identity->property(),
                    ),
                    Sign::equality,
                    $id->value(),
                )),
            fn() => Delete::from($this->name)
                ->join(
                    Join::left($this->main)->on(
                        Column\Name::of($this->definition->name())->in($this->main),
                        Column\Name::of('id')->in($this->name),
                    ),
                )
                ->where(PropertySpecification::of(
                    \sprintf(
                        '%s.%s',
                        $this->main->alias(),
                        $this->identity->property(),
                    ),
                    Sign::equality,
                    $id->value(),
                )),
        );
    }
}
