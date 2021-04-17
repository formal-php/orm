<?php
declare(strict_types = 1);

namespace Formal\ORM\SQL;

use Formal\ORM\Id;
use Innmind\Specification\{
    Comparator,
    Sign,
    Composable,
};

/**
 * @psalm-immutable
 */
final class MatchId implements Comparator
{
    use Composable;

    private string $property;
    private Id $id;

    public function __construct(string $property, Id $id)
    {
        $this->property = $property;
        $this->id = $id;
    }

    public function property(): string
    {
        return $this->property;
    }

    public function sign(): Sign
    {
        return Sign::equality();
    }

    public function value()
    {
        return $this->id->toString();
    }
}
