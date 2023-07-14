<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
};
use Fixtures\Formal\ORM\User;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Manager>
 */
final class AddAggregate implements Property
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
        $manager
            ->repository(User::class)
            ->put($user = User::new());
        $id = $user->id()->toString();
        unset($user); // to make sure there is no in memory cache somewhere

        $assert
            ->expected($current + 1)
            ->same(
                $manager
                    ->repository(User::class)
                    ->all()
                    ->size(),
            );
        $assert
            ->expected(1)
            ->same(
                $manager
                    ->repository(User::class)
                    ->all()
                    ->filter(static fn($user) => $user->id()->toString() === $id)
                    ->size(),
            );
        $assert
            ->expected($id)
            ->same(
                $manager
                    ->repository(User::class)
                    ->get(Id::of(User::class, $id))
                    ->match(
                        static fn($user) => $user->id()->toString(),
                        static fn() => null,
                    ),
            );

        return $manager;
    }
}
