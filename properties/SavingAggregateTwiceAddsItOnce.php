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
use Innmind\Immutable\Either;
use Fixtures\Innmind\Time\Point;

/**
 * @implements Property<Manager>
 */
final class SavingAggregateTwiceAddsItOnce implements Property
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

        $_ = $manager->transactional(
            static function() use ($manager, $user) {
                $_ = $manager
                    ->repository(User::class)
                    ->put($user)
                    ->unwrap();
                $_ = $manager
                    ->repository(User::class)
                    ->put($user)
                    ->unwrap();

                return Either::right(null);
            },
        );

        $assert
            ->expected($current + 1)
            ->same(
                $manager
                    ->repository(User::class)
                    ->size(),
            );

        return $manager;
    }
}
