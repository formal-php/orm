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
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class FailingTransactionDueToException implements Property
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

        try {
            $expected = new \Exception;
            $manager->transactional(
                function() use ($manager, $user, $assert, $initialSize, $expected) {
                    $manager
                        ->repository(User::class)
                        ->put($user);
                    $manager
                        ->repository(User::class)
                        ->remove($user->id());
                    $this->validate($assert, $manager, $user, $initialSize);

                    throw $expected;
                },
            );
        } catch (\Throwable $e) {
            $assert->same($expected, $e);
        }

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
