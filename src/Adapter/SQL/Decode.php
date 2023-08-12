<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
};
use Formal\AccessLayer\{
    Row,
    Table\Column,
};
use Innmind\Immutable\{
    Maybe,
    Set,
    Str,
};

/**
 * @template T of object
 */
final class Decode
{
    /** @var Definition<T> */
    private Definition $definition;
    /** @var MainTable<T> */
    private MainTable $mainTable;
    /** @var non-empty-string */
    private string $entityPrefix;
    /** @var non-empty-string */
    private string $id;

    /**
     * @param Definition<T> $definition
     * @param MainTable<T> $mainTable
     */
    private function __construct(Definition $definition, MainTable $mainTable)
    {
        $this->definition = $definition;
        $this->mainTable = $mainTable;
        $this->entityPrefix = $mainTable->name()->alias().'_';
        $this->id = $this->entityPrefix.$definition->id()->property();
    }

    /**
     * @return callable(Row): Maybe<Aggregate>
     */
    public function __invoke(Aggregate\Id $id = null): callable
    {
        /** @psalm-suppress MixedArgument */
        $id = match ($id) {
            null => fn(Row $row) => $row
                ->column($this->id)
                ->filter(\is_string(...))
                ->map(fn($id) => Aggregate\Id::of(
                    $this->id,
                    $id,
                )),
            default => static fn(Row $row) => Maybe::just($id),
        };

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress ArgumentTypeCoercion
         */
        return fn(Row $row) => $id($row)
            ->map(fn($id) => Aggregate::of(
                $id,
                $row
                    ->values()
                    ->filter(fn($value) => Str::of($value->column()->toString())->startsWith($this->entityPrefix))
                    ->map(static fn($value) => Aggregate\Property::of(
                        Str::of($value->column()->toString())->drop(7)->toString(),
                        $value->value(),
                    ))
                    ->toSet(),
                $this
                    ->mainTable
                    ->entities()
                    ->map(
                        static fn($entity) => Aggregate\Entity::of(
                            $entity->name()->alias(),
                            $entity
                                ->columns()
                                ->flatMap(
                                    static fn($column) => $row
                                        ->column($column->alias())
                                        ->map(static fn($value) => Aggregate\Property::of(
                                            match (true) {
                                                $column->name() instanceof Column\Name => $column->name()->toString(),
                                                $column->name() instanceof Column\Name\Namespaced => $column->name()->column()->toString(),
                                            },
                                            $value,
                                        ))
                                        ->toSequence()
                                        ->toSet(),
                                ),
                        ),
                    ),
                $this
                    ->definition
                    ->optionals()
                    ->map(static fn($optional) => Aggregate\Optional::of(
                        $optional->name(),
                        Maybe::nothing(), // TODO
                    )),
                $this
                    ->definition
                    ->collections()
                    ->map(static fn($collection) => Aggregate\Collection::of(
                        $collection->name(),
                        Set::of(), // TODO
                    )),
            ));
    }

    /**
     * @template A of object
     *
     * @param Definition<A> $definition
     * @param MainTable<A> $mainTable
     *
     * @return self<A>
     */
    public static function of(Definition $definition, MainTable $mainTable): self
    {
        return new self($definition, $mainTable);
    }
}
