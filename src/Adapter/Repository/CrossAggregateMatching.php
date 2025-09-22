<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Repository;

use Formal\ORM\Sort;
use Innmind\Specification\Specification;
use Innmind\Immutable\Maybe;

interface CrossAggregateMatching
{
    /**
     * @psalm-mutation-free
     *
     * @param ?int<1, max> $drop
     * @param ?int<1, max> $take
     *
     * @return Maybe<SubMatch>
     */
    public function crossAggregateMatching(
        ?Specification $specification,
        null|Sort\Property|Sort\Entity $sort,
        ?int $drop,
        ?int $take,
    ): Maybe;
}
