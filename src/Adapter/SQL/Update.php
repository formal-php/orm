<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\Raw\Diff;
use Formal\AccessLayer\Query;
use Innmind\Immutable\Sequence;

/**
 * @internal
 * @psalm-immutable
 * @template T of object
 */
final class Update
{
    /** @var MainTable<T> */
    private MainTable $mainTable;

    /**
     * @param MainTable<T> $mainTable
     */
    private function __construct(MainTable $mainTable)
    {
        $this->mainTable = $mainTable;
    }

    /**
     * @return Sequence<Query>
     */
    public function __invoke(Diff $data): Sequence
    {
        $main = $this->mainTable->update($data)->toSequence();
        $entities = $data
            ->entities()
            ->flatMap(
                fn($entity) => $this
                    ->mainTable
                    ->entity($entity->name())
                    ->map(
                        static fn($table) => $table
                            ->update(
                                $data->id(),
                                $entity->properties(),
                            )
                            ->toSequence(),
                    )
                    ->toSequence()
                    ->toSet(),
            );
        $optionals = $data
            ->optionals()
            ->flatMap(
                fn($optional) => $this
                    ->mainTable
                    ->optional($optional->name())
                    ->map(static fn($table) => $table->update(
                        $data->id(),
                        $optional,
                    ))
                    ->toSequence()
                    ->toSet(),
            );
        $collections = $data
            ->collections()
            ->flatMap(
                fn($collection) => $this
                    ->mainTable
                    ->collection($collection->name())
                    ->map(static fn($table) => $table->update(
                        $data->id(),
                        $collection->newEntities(),
                    ))
                    ->toSequence()
                    ->toSet(),
            );

        return Sequence::of(
            $main,
            ...$entities->toList(),
            ...$optionals->toList(),
            ...$collections->toList(),
        )->flatMap(static fn($queries) => $queries);
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
}
