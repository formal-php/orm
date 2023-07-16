<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Innmind\BlackBox\{
    Set,
    Property,
};

final class Properties
{
    public static function any(): Set\Properties
    {
        return Set\Properties::any(
            ...\array_map(
                static fn($property) => [$property, 'any'](),
                self::list(),
            ),
        );
    }

    /**
     * @return non-empty-list<class-string<Property>>
     */
    public static function list(): array
    {
        return [
            AddAggregate::class,
            SavingAggregateTwiceAddsItOnce::class,
            ContainsAggregate::class,
            DeleteUnknownAggregateDoesNothing::class,
            DeleteAggregate::class,
            Size::class,
            SizeWithSpecification::class,
            Matching::class,
        ];
    }

    /**
     * @return non-empty-list<class-string<Property>>
     */
    public static function alwaysApplicable(): array
    {
        return [
            AddAggregate::class,
            SavingAggregateTwiceAddsItOnce::class,
            ContainsAggregate::class,
            DeleteUnknownAggregateDoesNothing::class,
            DeleteAggregate::class,
            Size::class,
            SizeWithSpecification::class,
            Matching::class,
        ];
    }
}
