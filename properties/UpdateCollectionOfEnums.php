<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
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
final class UpdateCollectionOfEnums implements Property
{
    private $createdAt;
    private array $roles;
    private array $newRoles;

    private function __construct(
        $createdAt,
        array $roles,
        array $newRoles,
    ) {
        $this->createdAt = $createdAt;
        $this->roles = $roles;
        $this->newRoles = $newRoles;
    }

    public static function any(): Set\Provider
    {
        $roles = Set::of(
            [Role::admin],
            [Role::user],
            [Role::guest],
            [Role::admin, Role::user],
            [Role::admin, Role::guest],
            [Role::admin, Role::user, Role::guest],
            [Role::user, Role::guest],
        );

        return Set::compose(
            static fn(...$args) => new self(...$args),
            Point::any(),
            $roles,
            $roles,
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $repository = $manager->repository(User::class);
        $user = User::new($this->createdAt)->useRoles(...$this->roles);

        $manager->transactional(
            static fn() => $repository
                ->put($user)
                ->either(),
        );
        $id = $user->id()->toString();
        unset($user); // to make sure there is no in memory cache somewhere

        $loaded = $repository
            ->get(Id::of(User::class, $id))
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert->not()->null($loaded);
        $assert->same(\count($this->roles), $loaded->roles()->size());

        foreach ($this->roles as $role) {
            $assert->true($loaded->roles()->contains($role));
        }

        $user = $loaded->useRoles(...$this->newRoles);

        $manager->transactional(
            static fn() => $repository
                ->put($user)
                ->either(),
        );

        $reloaded = $repository
            ->get($user->id())
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert->not()->null($reloaded);
        $assert->same(\count($this->newRoles), $reloaded->roles()->size());

        foreach ($this->newRoles as $role) {
            $assert->true($reloaded->roles()->contains($role));
        }

        return $manager;
    }
}
