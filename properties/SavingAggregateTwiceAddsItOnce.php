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
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

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
            ->all()
            ->sequence()
            ->size();

        $user = User::new($this->createdAt);

        $manager->transactional(
            static function() use ($manager, $user) {
                $manager
                    ->repository(User::class)
                    ->put($user);
                $manager
                    ->repository(User::class)
                    ->put($user);

                return Either::right(null);
            },
        );

        $assert
            ->expected($current + 1)
            ->same(
                $manager
                    ->repository(User::class)
                    ->all()
                    ->sequence()
                    ->size(),
            );

        return $manager;
    }
}
