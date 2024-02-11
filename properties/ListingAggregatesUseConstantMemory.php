<?php
declare(strict_types = 1);
declare(ticks = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
};
use Fixtures\Formal\ORM\User;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Innmind\Immutable\Either;
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class ListingAggregatesUseConstantMemory implements Property
{
    private function __construct()
    {
    }

    public static function any(): Set
    {
        return Set\Elements::of(new self);
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $repository = $manager->repository(User::class);

        $assert
            ->memory(
                static function() use ($repository) {
                    $_ = $repository
                        ->all()
                        ->foreach(static fn() => null);
                },
            )
            ->inLessThan()
            ->megaBytes(1);

        return $manager;
    }
}
