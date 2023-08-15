<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\Manager;
use Fixtures\Formal\ORM\{
    User,
    Ids,
};
use Innmind\Immutable\Sequence;
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
final class MatchingIds implements Property
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
        $user1 = User::new($this->createdAt);
        $user2 = User::new($this->createdAt);
        $user3 = User::new($this->createdAt);

        $repository = $manager->repository(User::class);
        $manager->transactional(
            static function() use ($repository, $user1, $user2, $user3) {
                $repository->put($user1);
                $repository->put($user2);
                $repository->put($user3);

                return Either::right(null);
            },
        );

        $found = $repository
            ->matching(Ids::in(Sequence::of($user1->id())))
            ->sequence()
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($user1->id()->toString())
            ->in($found);
        $assert
            ->expected($user2->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($user3->id()->toString())
            ->not()
            ->in($found);

        $found = $repository
            ->matching(Ids::in(Sequence::of($user2->id(), $user3->id())))
            ->sequence()
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($user1->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($user2->id()->toString())
            ->in($found);
        $assert
            ->expected($user3->id()->toString())
            ->in($found);

        return $manager;
    }
}
