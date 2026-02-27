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
final class FailingTransactionDueToLeftSide implements Property
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
        $user = User::new($this->createdAt);
        $initialSize = $manager->repository(User::class)->size();

        $manager->transactional(
            function() use ($manager, $user, $assert, $initialSize) {
                $_ = $manager
                    ->repository(User::class)
                    ->put($user)
                    ->unwrap();
                $_ = $manager
                    ->repository(User::class)
                    ->remove($user->id())
                    ->unwrap();
                $this->validate($assert, $manager, $user, $initialSize);

                return Either::left(null);
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
        $assert->false(
            $manager
                ->repository(User::class)
                ->contains($user->id()),
        );
        $assert->false(
            $manager
                ->repository(User::class)
                ->get($user->id())
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );
        $assert
            ->expected($initialSize)
            ->same($manager->repository(User::class)->size());
        $assert
            ->expected($user->id()->toString())
            ->not()
            ->in(
                $manager
                    ->repository(User::class)
                    ->all()
                    ->map(static fn($user) => $user->id()->toString())
                    ->toList(),
            );
    }
}
