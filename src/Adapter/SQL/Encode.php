<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
};
use Formal\AccessLayer\Query;
use Innmind\Immutable\{
    Set,
    Sequence,
};

/**
 * @internal
 * @psalm-immutable
 * @template T of object
 */
final class Encode
{
    /** @var Definition<T> */
    private Definition $definition;
    /** @var MainTable<T> */
    private MainTable $mainTable;

    /**
     * @param Definition<T> $definition
     * @param MainTable<T> $mainTable
     */
    private function __construct(
        Definition $definition,
        MainTable $mainTable,
    ) {
        $this->definition = $definition;
        $this->mainTable = $mainTable;
    }

    /**
     * @return Sequence<Query>
     */
    public function __invoke(Aggregate $data): Sequence
    {
        $main = $this->main($data);
        $entities = $this->entities($data);
        $optionals = $this->optionals($data);
        $collections = $this->collections($data);

        return Sequence::of(
            $main,
            ...$entities->toList(),
            ...$optionals->toList(),
            ...$collections->toList(),
        );
    }

    /**
     * @internal
     * @psalm-pure
     * @template A of object
     *
     * @param Definition<A> $definition
     * @param MainTable<A> $mainTable
     *
     * @return self<A>
     */
    public static function of(
        Definition $definition,
        MainTable $mainTable,
    ): self {
        return new self($definition, $mainTable);
    }

    /**
     * @return Set<Query>
     */
    private function entities(Aggregate $data): Set
    {
        return $data
            ->entities()
            ->flatMap(
                fn($entity) => $this
                    ->mainTable
                    ->entity($entity->name())
                    ->map(
                        static fn($table) => $table->insert($data->id(), $entity->properties()),
                    )
                    ->toSequence()
                    ->toSet(),
            );
    }

    /**
     * @return Set<Query>
     */
    private function optionals(Aggregate $data): Set
    {
        return $data
            ->optionals()
            ->flatMap(
                fn($optional) => $this
                    ->mainTable
                    ->optional($optional->name())
                    ->flatMap(
                        static fn($table) => $optional->properties()->map(
                            static fn($properties) => $table->insert($data->id(), $properties),
                        ),
                    )
                    ->toSequence()
                    ->toSet(),
            );
    }

    private function main(Aggregate $data): Query
    {
        return $this->mainTable->insert(
            $data->id(),
            $data->properties(),
        );
    }

    /**
     * @return Set<Query>
     */
    private function collections(Aggregate $data): Set
    {
        return $data
            ->collections()
            ->flatMap(
                fn($collection) => $this
                    ->mainTable
                    ->collection($collection->name())
                    ->flatMap(
                        static fn($table) => $table->insert(
                            $data->id(),
                            $collection->properties(),
                        ),
                    )
                    ->toSequence()
                    ->toSet(),
            );
    }
}
