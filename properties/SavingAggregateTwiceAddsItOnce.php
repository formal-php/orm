<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\Manager;
use Fixtures\Formal\ORM\User;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Manager>
 */
final class SavingAggregateTwiceAddsItOnce implements Property
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
        $current = $manager
            ->repository(User::class)
            ->all()
            ->size();

        $user = User::new();

        $manager
            ->repository(User::class)
            ->put($user);
        $manager
            ->repository(User::class)
            ->put($user);

        $assert
            ->expected($current + 1)
            ->same(
                $manager
                    ->repository(User::class)
                    ->all()
                    ->size(),
            );

        return $manager;
    }
}
