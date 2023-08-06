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
    Map,
};
use Ramsey\Uuid\Uuid;

/**
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
        $entities = $this->entities($data);
        $main = $this->main($data, $entities->keys());

        return $entities
            ->values()
            ->add($main);
    }

    /**
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
     * @return Map<array{non-empty-string, non-empty-string}, Query>
     */
    private function entities(Aggregate $data): Map
    {
        $inserts = $data
            ->entities()
            ->flatMap(
                fn($entity) => $this
                    ->mainTable
                    ->entity($entity->name())
                    ->map(
                        static fn($table) => [
                            [$entity->name(), $uuid = Uuid::uuid4()->toString()],
                            $table->insert($uuid, $entity->properties()),
                        ],
                    )
                    ->toSequence()
                    ->toSet(),
            );

        return Map::of(...$inserts->toList());
    }

    /**
     * @param Set<array{non-empty-string, non-empty-string}> $entities
     */
    private function main(Aggregate $data, Set $entities): Query
    {
        return $this
            ->mainTable
            ->insert(
                $data->id()->value(),
                $data->properties(),
                Map::of(...$entities->toList()),
            );
    }
}
