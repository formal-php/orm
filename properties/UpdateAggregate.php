<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
    Definition\Type\PointInTimeType\Format,
};
use Fixtures\Formal\ORM\User;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Innmind\TimeContinuum\Earth\Timezone\UTC;
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class UpdateAggregate implements Property
{
    private string $name;
    private string $newName;
    private $createdAt;

    private function __construct(string $name, string $newName, $createdAt)
    {
        $this->name = $name;
        $this->newName = $newName;
        $this->createdAt = $createdAt;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Strings::madeOf(Set\Chars::alphanumerical()),
            Set\Strings::madeOf(Set\Chars::alphanumerical()),
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
        $repository->put($user = User::new($this->createdAt, $this->name));
        $id = $user->id()->toString();
        unset($user); // to make sure there is no in memory cache somewhere

        $loaded = $repository
            ->get(Id::of(User::class, $id))
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert->not()->null($loaded);

        $user = $loaded->rename($this->newName);
        $manager
            ->repository(User::class)
            ->put($user);

        $reloaded = $repository
            ->get(Id::of(User::class, $id))
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

        // make sure the diff is correctly updated
        $user = $reloaded->rename($this->name);
        $repository->put($user);

        $back = $repository
            ->get(Id::of(User::class, $id))
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
