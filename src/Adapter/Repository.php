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
    public function add(Aggregate $data): void;
    public function update(Diff $data): void;
    public function remove(Aggregate\Id $id): void;

    /**
     * @param ?array{non-empty-string, Sort} $sort
     * @param ?positive-int $drop
     * @param ?positive-int $take
     *
     * @return Sequence<Aggregate>
     */
    public function matching(
        Specification $specification,
        ?array $sort,
        ?int $drop,
        ?int $take,
    ): Sequence;

    /**
     * @return 0|positive-int
     */
    public function size(Specification $specification = null): int;

    /**
     * @return Sequence<Aggregate>
     */
    public function all(): Sequence;
}
