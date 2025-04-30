<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Effect,
    Id,
};
use Fixtures\Formal\ORM\User;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Innmind\Specification\{
    Comparator,
    Sign,
};
use Innmind\Immutable\Either;
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class EffectEntityPropertiesOnAggregate implements Property
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
            Set::strings()
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
        $user = User::new($this->createdAt, $this->name);
        $manager->transactional(
            static fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->put($user),
            ),
        );
        $id = $user->id()->toString();
        unset($user); // to make sure there is no in memory cache somewhere

        $specification = Comparator\Property::of(
            'id',
            Sign::equality,
            Id::of(User::class, $id),
        );

        $manager->transactional(
            fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->effect(
                        Effect::entity('mainAddress')->properties(
                            Effect::property('value')
                                ->assign($this->address)
                                ->and(
                                    Effect::property('enabled')->assign(
                                        $this->enabled,
                                    ),
                                ),
                        ),
                        $specification,
                    ),
            ),
        );

        $manager
            ->repository(User::class)
            ->matching($specification)
            ->foreach(
                fn($user) => $assert
                    ->expected($user->mainAddress()->toString())
                    ->same($this->address),
            );

        $manager
            ->repository(User::class)
            ->matching($specification)
            ->foreach(
                fn($user) => $assert
                    ->expected($user->mainAddress()->enabled())
                    ->same($this->enabled),
            );

        $manager
            ->repository(User::class)
            ->matching($specification->not())
            ->foreach(
                fn($user) => $assert
                    ->expected($user->mainAddress()->toString())
                    ->not()
                    ->same($this->address),
            );

        return $manager;
    }
}
