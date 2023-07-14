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
final class DeleteAggregate implements Property
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
            ->delete($user->id());

        $assert->false(
            $manager
                ->repository(User::class)
                ->contains($user->id()),
        );
        $assert->null(
            $manager
                ->repository(User::class)
                ->get($user->id())
                ->match(
                    static fn($user) => $user,
                    static fn() => null,
                ),
        );
        $assert
            ->expected($current)
            ->same(
                $manager
                    ->repository(User::class)
                    ->all()
                    ->size(),
                $user->id()->toString(),
            );

        return $manager;
    }
}
