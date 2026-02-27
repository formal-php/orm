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
use Fixtures\Innmind\Time\Point;

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
        $_ = $manager->transactional(
            static fn() => $manager
                ->repository(User::class)
                ->put($user)
                ->either(),
        );
        $id = $user->id()->toString();
        unset($user); // to make sure there is no in memory cache somewhere

        $specification = Comparator\Property::of(
            'id',
            Sign::equality,
            Id::of(User::class, $id),
        );

        $_ = $manager->transactional(
            fn() => $manager
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
                )
                ->either(),
        );

        $_ = $manager
            ->repository(User::class)
            ->matching($specification)
            ->foreach(
                fn($user) => $assert
                    ->expected($user->mainAddress()->toString())
                    ->same($this->address),
            );

        $_ = $manager
            ->repository(User::class)
            ->matching($specification)
            ->foreach(
                fn($user) => $assert
                    ->expected($user->mainAddress()->enabled())
                    ->same($this->enabled),
            );

        $_ = $manager
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
