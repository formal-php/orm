<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
    Specification\Child\Enum,
};
use Fixtures\Formal\ORM\{
    User,
    Role,
};
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
final class MatchingCollectionOfEnums implements Property
{
    private $createdAt;

    private function __construct(
        $createdAt,
    ) {
        $this->createdAt = $createdAt;
    }

    public static function any(): Set
    {
        return Set\Decorate::immutable(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $repository = $manager->repository(User::class);
        $admin = User::new($this->createdAt)->useRoles(Role::admin);
        $user = User::new($this->createdAt)->useRoles(Role::user);
        $guest = User::new($this->createdAt)->useRoles(Role::guest);

        $manager->transactional(
            static fn() => Either::right($repository->put($admin))
                ->map(static fn() => $repository->put($user))
                ->map(static fn() => $repository->put($guest)),
        );

        $found = $repository
            ->matching(Enum::any('roles', Role::admin))
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($admin->id()->toString())
            ->in($found);
        $assert
            ->expected($guest->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($user->id()->toString())
            ->not()
            ->in($found);

        $found = $repository
            ->matching(Enum::in('roles', Role::admin, Role::guest))
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($admin->id()->toString())
            ->in($found);
        $assert
            ->expected($user->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($guest->id()->toString())
            ->in($found);

        return $manager;
    }
}
