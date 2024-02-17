<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
    Definition\Type\PointInTimeType\Format,
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
use Innmind\TimeContinuum\Earth\Timezone\UTC;
use Innmind\Immutable\Either;
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class UpdateAggregate implements Property
{
    private string $name;
    private string $newName;
    private $createdAt;
    private Role $role;

    private function __construct(
        string $name,
        string $newName,
        $createdAt,
        Role $role,
    ) {
        $this->name = $name;
        $this->newName = $newName;
        $this->createdAt = $createdAt;
        $this->role = $role;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Strings::madeOf(Set\Chars::alphanumerical()),
            Set\Strings::madeOf(Set\Chars::alphanumerical()),
            PointInTime::any(),
            Set\Elements::of(...Role::cases()),
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $repository = $manager->repository(User::class);
        $user = User::new($this->createdAt, $this->name);

        $manager->transactional(
            static fn() => Either::right($repository->put($user)),
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

        $user = $loaded
            ->rename($this->newName)
            ->useRole($this->role);
        $manager->transactional(
            static fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->put($user),
            ),
        );

        $reloaded = $repository
            ->get($user->id())
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert->not()->null($reloaded);
        $assert
            ->expected($this->newName)
            ->same($reloaded->name())
            ->same($reloaded->nameStr()->toString());
        $assert
            ->expected(
                $this
                    ->createdAt
                    ->changeTimezone(new UTC)
                    ->format(new Format),
            )
            ->same(
                $reloaded
                    ->createdAt()
                    ->changeTimezone(new UTC)
                    ->format(new Format),
            );
        $assert->same(
            $this->role,
            $reloaded->role()->match(
                static fn($role) => $role,
                static fn() => null,
            ),
        );

        // make sure the diff is correctly updated
        $user = $reloaded->rename($this->name);

        $manager->transactional(
            static fn() => Either::right(
                $repository->put($user),
            ),
        );

        $back = $repository
            ->get($user->id())
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert->not()->null($back);
        $assert
            ->expected($this->name)
            ->same($back->name())
            ->same($back->nameStr()->toString());

        return $manager;
    }
}
