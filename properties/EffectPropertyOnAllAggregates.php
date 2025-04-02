<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Effect,
};
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
final class EffectPropertyOnAllAggregates implements Property
{
    private ?string $name;
    private ?string $newName;
    private $createdAt;

    private function __construct(?string $name, ?string $newName, $createdAt)
    {
        $this->name = $name;
        $this->newName = $newName;
        $this->createdAt = $createdAt;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Nullable::of(Set\Strings::madeOf(Set\Chars::alphanumerical())),
            Set\Nullable::of(Set\Strings::madeOf(Set\Chars::alphanumerical())),
            PointInTime::any(),
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $current = $manager
            ->repository(User::class)
            ->size();
        $user = User::new($this->createdAt, $this->name);
        $manager->transactional(
            static fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->put($user),
            ),
        );
        unset($user); // to make sure there is no in memory cache somewhere

        $manager->transactional(
            fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->effect(Effect\Property::assign(
                        'name',
                        $this->newName,
                    )),
            ),
        );

        $manager
            ->repository(User::class)
            ->all()
            ->foreach(fn($user) => $assert->same(
                $this->newName,
                $user->name(),
            ));

        return $manager;
    }
}
