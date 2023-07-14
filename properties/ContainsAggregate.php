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
final class ContainsAggregate implements Property
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
        $user = User::new();

        $assert->false(
            $manager
                ->repository(User::class)
                ->contains($user->id()),
        );

        $manager
            ->repository(User::class)
            ->put($user);

        $assert->true(
            $manager
                ->repository(User::class)
                ->contains($user->id()),
        );

        return $manager;
    }
}
