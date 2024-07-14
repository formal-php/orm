<?php
declare(strict_types = 1);

namespace Formal\ORM\Specification\Child;

use Formal\ORM\Specification\Child;
use Innmind\Specification\{
    Comparator\Property,
    Sign,
};
use Innmind\Immutable\Set;

final class Enum
{
    /**
     * @psalm-pure
     */
    public static function any(string $collection, \UnitEnum $value): Child
    {
        return Child::of($collection, Property::of(
            'name',
            Sign::equality,
            $value->name,
        ));
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     */
    public static function in(
        string $collection,
        \UnitEnum $first,
        \UnitEnum ...$rest,
    ): Child {
        return Child::of($collection, Property::of(
            'name',
            Sign::in,
            Set::of($first, ...$rest)->map(static fn($case) => $case->name),
        ));
    }
}
