<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
    Definition\Type\PointInTimeType\Formats,
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
use Innmind\Time\Offset;
use Fixtures\Innmind\Time\Point;

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

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::strings()->madeOf(Set::strings()->chars()->alphanumerical()),
            Set::strings()->madeOf(Set::strings()->chars()->alphanumerical()),
            Point::any(),
            Set::of(...Role::cases()),
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

        $_ = $manager->transactional(
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

        $user = $loaded
            ->rename($this->newName)
            ->useRole($this->role);
        $_ = $manager->transactional(
            static fn() => $manager
                ->repository(User::class)
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
        $assert
            ->expected($this->newName)
            ->same($reloaded->name())
            ->same($reloaded->nameStr()->toString());
        $assert
            ->expected(
                $this
                    ->createdAt
                    ->changeOffset(Offset::utc())
                    ->format(Formats::default),
            )
            ->same(
                $reloaded
                    ->createdAt()
                    ->changeOffset(Offset::utc())
                    ->format(Formats::default),
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

        $_ = $manager->transactional(
            static fn() => $repository
                ->put($user)
                ->either(),
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
