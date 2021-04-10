<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Innmind\Immutable\{
    Maybe,
    Set,
};
use Innmind\Specification\Specification;

/**
 * @template T of object
 */
interface Repository
{
    /**
     * @param Id<T> $id
     *
     * @return Maybe<T>
     */
    public function get(Id $id): Maybe;

    /**
     * @param T $entity
     *
     * @throws \LogicException When not in a transaction
     */
    public function add(object $entity): void;

    /**
     * @param Id<T> $id
     *
     * @throws \LogicException When not in a transaction
     */
    public function remove(Id $id): void;

    /**
     * @return Set<T>
     */
    public function all(): Set;

    /**
     * @return Set<T>
     */
    public function matching(Specification $specification): Set;
}
