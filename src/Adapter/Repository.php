<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter;

use Formal\ORM\{
    Raw\Aggregate,
    Raw\Diff,
    Sort,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Attempt,
    SideEffect,
};

/**
 * @template T of object
 */
interface Repository
{
    /**
     * @return Maybe<Aggregate>
     */
    public function get(Aggregate\Id $id): Maybe;
    public function contains(Aggregate\Id $id): bool;

    /**
     * @return Attempt<SideEffect>
     */
    public function add(Aggregate $data): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function update(Diff $data): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function remove(Aggregate\Id $id): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function removeAll(Specification $specification): Attempt;

    /**
     * @param ?int<1, max> $drop
     * @param ?int<1, max> $take
     *
     * @return Sequence<Aggregate>
     */
    public function fetch(
        ?Specification $specification,
        null|Sort\Property|Sort\Entity $sort,
        ?int $drop,
        ?int $take,
    ): Sequence;

    /**
     * @return int<0, max>
     */
    public function size(?Specification $specification = null): int;

    public function any(?Specification $specification = null): bool;
}
