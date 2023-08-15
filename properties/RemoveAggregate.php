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
use Innmind\Immutable\Either;
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class RemoveAggregate implements Property
{
    private $createdAt;

    private function __construct($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public static function any(): Set
    {
        return PointInTime::any()->map(static fn($createdAt) => new self($createdAt));
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $current = $manager
            ->repository(User::class)
            ->size();

        $user = User::new($this->createdAt);
        $manager->transactional(
            static fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->put($user),
            ),
        );

        $manager->transactional(
            static fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->remove($user->id()),
            ),
        );

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
                    ->size(),
                $user->id()->toString(),
            );

        return $manager;
    }
}
