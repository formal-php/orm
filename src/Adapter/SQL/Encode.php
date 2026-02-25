<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\Raw\Aggregate;
use Formal\AccessLayer\Query;
use Innmind\Immutable\Sequence;

/**
 * @internal
 * @psalm-immutable
 * @template T of object
 */
final class Encode
{
    /**
     * @param MainTable<T> $mainTable
     */
    private function __construct(private MainTable $mainTable)
    {
    }

    /**
     * @return Sequence<Query\Builder>
     */
    public function __invoke(Aggregate $data): Sequence
    {
        $main = $this->main($data);
        $entities = $this->entities($data);
        $optionals = $this->optionals($data);
        $collections = $this->collections($data);

        return Sequence::of($main)
            ->append($entities)
            ->append($optionals)
            ->append($collections);
    }

    /**
     * @internal
     * @psalm-pure
     * @template A of object
     *
     * @param MainTable<A> $mainTable
     *
     * @return self<A>
     */
    public static function of(MainTable $mainTable): self
    {
        return new self($mainTable);
    }

    /**
     * @return Sequence<Query\Builder>
     */
    private function entities(Aggregate $data): Sequence
    {
        $mainTable = $this->mainTable;

        return $data
            ->entities()
            ->flatMap(
                static fn($entity) => $mainTable
                    ->entity($entity->name())
                    ->map(
                        static fn($table) => $table->insert($data->id(), $entity->properties()),
                    )
                    ->toSequence(),
            );
    }

    /**
     * @return Sequence<Query\Builder>
     */
    private function optionals(Aggregate $data): Sequence
    {
        $mainTable = $this->mainTable;

        return $data
            ->optionals()
            ->flatMap(
                static fn($optional) => $mainTable
                    ->optional($optional->name())
                    ->flatMap(
                        static fn($table) => $optional->properties()->map(
                            static fn($properties) => $table->insert($data->id(), $properties),
                        ),
                    )
                    ->toSequence(),
            );
    }

    private function main(Aggregate $data): Query\Builder
    {
        return $this->mainTable->insert(
            $data->id(),
            $data->properties(),
        );
    }

    /**
     * @return Sequence<Query\Builder>
     */
    private function collections(Aggregate $data): Sequence
    {
        $mainTable = $this->mainTable;

        return $data
            ->collections()
            ->flatMap(
                static fn($collection) => $mainTable
                    ->collection($collection->name())
                    ->toSequence()
                    ->flatMap(
                        static fn($table) => $table->insert(
                            $data->id(),
                            $collection->entities(),
                        ),
                    ),
            );
    }
}
