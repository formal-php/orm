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
final class EffectEntityPropertiesOnAllAggregates implements Property
{
    private function __construct(
        private ?string $name,
        private string $address,
        private bool $enabled,
        private $createdAt,
    ) {
    }

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->atLeast(10) // to limit collisions
                ->nullable(),
            Set::strings() // not nullable to avoid collisions
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->atLeast(10), // to limit collisions
            Set::of(true, false),
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
                    ->effect(
                        Effect\Entity::of(
                            'mainAddress',
                            Effect\Property::assign(
                                'value',
                                $this->address,
                            )->and(
                                Effect\Property::assign(
                                    'enabled',
                                    $this->enabled,
                                ),
                            ),
                        ),
                    ),
            ),
        );

        $manager
            ->repository(User::class)
            ->all()
            ->foreach(
                fn($user) => $assert
                    ->expected($user->mainAddress()->toString())
                    ->same($this->address),
            );

        $manager
            ->repository(User::class)
            ->all()
            ->foreach(
                fn($user) => $assert
                    ->expected($user->mainAddress()->enabled())
                    ->same($this->enabled),
            );

        return $manager;
    }
}
