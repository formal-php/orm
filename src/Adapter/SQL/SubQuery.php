<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\AccessLayer\Query;
use Innmind\Specification\{
    Comparator,
    Sign,
    Composable,
};

/**
 * @psalm-immutable
 * @internal
 */
final class SubQuery implements Comparator
{
    use Composable;

    /**
     * @param non-empty-string $property
     */
    private function __construct(
        private string $property,
        private Query|Query\Builder $query,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(string $property, Query|Query\Builder $query): self
    {
        return new self($property, $query);
    }

    #[\Override]
    public function property(): string
    {
        return $this->property;
    }

    #[\Override]
    public function sign(): Sign
    {
        return Sign::in;
    }

    #[\Override]
    public function value(): Query|Query\Builder
    {
        return $this->query;
    }
}
