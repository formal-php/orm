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
final class EffectEntityPropertyOnAggregate implements Property
{
    private ?string $name;
    private string $address;
    private $createdAt;

    private function __construct(?string $name, string $address, $createdAt)
    {
        $this->name = $name;
        $this->address = $address;
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
                ->atLeast(10), // to limit collisions
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
                            Effect::property('value')->assign(
                                $this->address,
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
