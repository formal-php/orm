<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Repository\CrossAggregateMatching;

use Formal\ORM\{
    Sort,
    Adapter\Repository\SubMatch,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\Maybe;

/**
 * @internal
 */
interface Property
{
    /**
     * @psalm-mutation-free
     *
     * @param non-empty-string $property
     * @param ?int<1, max> $drop
     * @param ?int<1, max> $take
     *
     * @return Maybe<SubMatch>
     */
    public function crossAggregateMatchingOnProperty(
        string $property,
        ?Specification $specification,
        null|Sort\Property|Sort\Entity $sort,
        ?int $drop,
        ?int $take,
    ): Maybe;
}
