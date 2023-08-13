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
final class SuccessfulTransaction implements Property
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
        $initialSize = $manager->repository(User::class)->size();

        $manager->transactional(
            function() use ($manager, $user, $assert, $initialSize) {
                $manager
                    ->repository(User::class)
                    ->put($user);
                $this->validate($assert, $manager, $user, $initialSize);

                return Either::right(null);
            },
        );

        $this->validate($assert, $manager, $user, $initialSize);

        return $manager;
    }

    private function validate(
        Assert $assert,
        Manager $manager,
        User $user,
        int $initialSize,
    ): void {
        $assert->true(
            $manager
                ->repository(User::class)
                ->contains($user->id()),
        );
        $assert->true(
            $manager
                ->repository(User::class)
                ->get($user->id())
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );
        $assert
            ->expected($initialSize + 1)
            ->same($manager->repository(User::class)->size());
        $assert
            ->expected($user->id()->toString())
            ->in(
                $manager
                    ->repository(User::class)
                    ->all()
                    ->fetch()
                    ->map(static fn($user) => $user->id()->toString())
                    ->toList(),
            );
    }
}
