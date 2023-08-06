<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate\Optional as Definition,
    Raw\Aggregate\Property,
};
use Formal\AccessLayer\{
    Table,
    Table\Column,
    Query,
    Row,
};
use Innmind\Immutable\Set;

/**
 * @template T of object
 */
final class OptionalTable
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
}
