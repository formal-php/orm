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

    /** @var non-empty-string */
    private string $property;
    private Query $query;

    /**
     * @param non-empty-string $property
     */
    private function __construct(
        string $property,
        Query $query,
    ) {
        $this->property = $property;
        $this->query = $query;
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(string $property, Query $query): self
    {
        return new self($property, $query);
    }

    public function property(): string
    {
        return $this->property;
    }

    public function sign(): Sign
    {
        return Sign::in;
    }

    public function value(): Query
    {
        return $this->query;
    }
}
