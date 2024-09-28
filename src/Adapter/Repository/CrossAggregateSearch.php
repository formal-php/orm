<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Repository;

use Formal\ORM\Sort;
use Innmind\Specification\Specification;
use Innmind\Immutable\Maybe;

interface CrossAggregateSearch
{
    /**
     * @psalm-mutation-free
     *
     * @param ?positive-int $drop
     * @param ?positive-int $take
     *
     * @return Maybe<SubMatch>
     */
    public function crossAggregateSearch(
        ?Specification $specification,
        null|Sort\Property|Sort\Entity $sort,
        ?int $drop,
        ?int $take,
    ): Maybe;
}
