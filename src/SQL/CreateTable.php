<?php
declare(strict_types = 1);

namespace Formal\ORM\SQL;

use Formal\ORM\Definition\Aggregate;
use Formal\AccessLayer\{
    Query,
    Table,
};
use function Innmind\Immutable\unwrap;

final class CreateTable
{
    private Types $types;

    public function __construct(Types $types)
    {
        $this->types = $types;
    }

    public function __invoke(Aggregate $aggregate): Query
    {
        $columns = $aggregate->properties()->mapTo(
            Table\Column::class,
            fn($property) => ($this->types)($property)->declaration($property),
        );

        $query = new Query\CreateTable(
            new Table\Name($aggregate->name()),
            ...unwrap($columns),
        );

        return $query->primaryKey(new Table\Column\Name(
            $aggregate->id()->property(),
        ));
    }
}
