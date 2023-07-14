<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter;

use Formal\ORM\Raw\Aggregate;
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
    // TODO public function size()

    /**
     * @return Sequence<Aggregate>
     */
    public function all(): Sequence;
}
