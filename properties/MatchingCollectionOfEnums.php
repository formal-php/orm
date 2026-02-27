<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
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
use Fixtures\Innmind\Time\Point;

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
        return Point::any()->map(static fn(...$args) => new self(...$args));
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

        $_ = $manager->transactional(
            static fn() => $repository
                ->put($admin)
                ->either()
                ->flatMap(static fn() => $repository->put($user)->either())
                ->flatMap(static fn() => $repository->put($guest)->either()),
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
