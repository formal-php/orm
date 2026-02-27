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
use Fixtures\Innmind\Time\Point;

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

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->atLeast(10) // to limit collisions
                ->nullable(),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->atLeast(10) // to limit collisions
                ->nullable(),
            Point::any(),
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $user = User::new($this->createdAt, $this->name);
        $manager->transactional(
            static fn() => $manager
                ->repository(User::class)
                ->put($user)
                ->either(),
        );
        unset($user); // to make sure there is no in memory cache somewhere

        $manager->transactional(
            fn() => $manager
                ->repository(User::class)
                ->effect(Effect::property('name')->assign(
                    $this->newName,
                ))
                ->either(),
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
