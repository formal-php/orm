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
use Fixtures\Innmind\Time\Point;

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
        return Point::any()->map(static fn($createdAt) => new self($createdAt));
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
            static fn() => $manager
                ->repository(User::class)
                ->put($user)
                ->either(),
        );

        $manager->transactional(
            static fn() => $manager
                ->repository(User::class)
                ->remove($user->id())
                ->either(),
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
