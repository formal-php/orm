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
                    ->flatMap(
                        static fn($table) => $table->update(
                            $data->id(),
                            $entity->properties(),
                        ),
                    )
                    ->toSequence(),
            );
        $optionals = $data
            ->optionals()
            ->flatMap(
                fn($optional) => $this
                    ->mainTable
                    ->optional($optional->name())
                    ->toSequence()
                    ->flatMap(static fn($table) => $table->update(
                        $data->id(),
                        $optional,
                    )),
            );
        $collections = $data
            ->collections()
            ->flatMap(
                fn($collection) => $this
                    ->mainTable
                    ->collection($collection->name())
                    ->toSequence()
                    ->flatMap(static fn($table) => $table->update(
                        $data->id(),
                        $collection->newEntities(),
                        $collection->unmodifiedEntities()->map(
                            static fn($entity) => $entity->reference(),
                        ),
                    )),
            );

        return $main
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
}
