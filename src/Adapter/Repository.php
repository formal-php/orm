<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter;

use Formal\ORM\Raw\Aggregate;
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
    public function update(Aggregate $data): void;
    public function delete(Aggregate\Id $id): void;
    // TODO public function matching()

    /**
     * @return 0|positive-int
     */
    public function size(Specification $specification = null): int;

    /**
     * @return Sequence<Aggregate>
     */
    public function all(): Sequence;
}
