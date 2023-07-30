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
final class ContainsAggregate implements Property
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
        $user = User::new($this->createdAt);

        $assert->false(
            $manager
                ->repository(User::class)
                ->contains($user->id()),
        );

        $manager->transactional(
            static fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->put($user),
            ),
        );

        $assert->true(
            $manager
                ->repository(User::class)
                ->contains($user->id()),
        );

        return $manager;
    }
}
