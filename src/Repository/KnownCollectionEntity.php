<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Id,
    Raw\Aggregate\Collection\Entity\Reference,
};
use Innmind\Immutable\Map;

final class KnownCollectionEntity
{
    /** @var \WeakMap<Id, Map<non-empty-string, \WeakMap<object, Reference>>> */
    private \WeakMap $aggregates;

    private function __construct()
    {
        /** @var \WeakMap<Id, Map<non-empty-string, \WeakMap<object, Reference>>> */
        $this->aggregates = new \WeakMap;
    }

    public static function new(): self
    {
        return new self;
    }

    /**
     * @template T of object
     *
     * @param \WeakReference<Id> $idReference
     * @param T $entity
     * @param non-empty-string $collection
     *
     * @return T
     */
    public function add(
        \WeakReference $idReference,
        string $collection,
        object $entity,
        Reference $reference,
    ): object {
        $id = $idReference->get();

        if (\is_null($id)) {
            // The aggregate can go out of memory in case the user throw out
            // the aggregate from memory but keep the collection.
            // In this case we can't keep track of the reference.
            return $entity;
        }

        /** @var Map<non-empty-string, \WeakMap<object, Reference>> */
        $aggregate = $this->aggregates[$id] ?? Map::of();
        /** @var \WeakMap<object, Reference> */
        $entities = $aggregate->get($collection)->match(
            static fn($entities) => $entities,
            static fn() => new \WeakMap,
        );
        $entities[$entity] = $reference;
        $aggregate = ($aggregate)($collection, $entities);
        $this->aggregates[$id] = $aggregate;

        return $entity;
    }

    /**
     * @template T of object
     *
     * @param T $entity
     * @param non-empty-string $collection
     */
    public function reference(
        Id $id,
        string $collection,
        object $entity,
    ): Reference {
        /** @var Map<non-empty-string, \WeakMap<object, Reference>> */
        $aggregate = $this->aggregates[$id] ?? Map::of();
        /** @var \WeakMap<object, Reference> */
        $entities = $aggregate->get($collection)->match(
            static fn($entities) => $entities,
            static fn() => new \WeakMap,
        );
        $reference = $entities[$entity] ?? Reference::new();
        $entities[$entity] = $reference;
        $aggregate = ($aggregate)($collection, $entities);
        $this->aggregates[$id] = $aggregate;

        return $reference;
    }
}
