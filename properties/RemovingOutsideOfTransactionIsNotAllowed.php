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
final class RemovingOutsideOfTransactionIsNotAllowed implements Property
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
        return $manager->repository(User::class)->any();
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $user = $manager
            ->repository(User::class)
            ->all()
            ->take(1)
            ->sequence()
            ->first()
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );

        $assert->throws(
            static fn() => $manager
                ->repository(User::class)
                ->remove($user->id()),
            \LogicException::class,
            'Mutation outside of a transaction',
        );

        return $manager;
    }
}
