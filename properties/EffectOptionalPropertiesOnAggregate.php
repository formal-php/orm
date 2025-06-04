<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Effect,
    Id,
};
use Fixtures\Formal\ORM\User;
use Innmind\Specification\{
    Comparator,
    Sign,
};
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Innmind\Immutable\Either;
use Fixtures\Innmind\TimeContinuum\PointInTime;

/**
 * @implements Property<Manager>
 */
final class EffectOptionalPropertiesOnAggregate implements Property
{
    private function __construct(
        private ?string $name,
        private string $address,
        private string $newAddress,
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
        $user = User::new($this->createdAt, $this->name)->changeBillingAddress(
            $this->address,
        );
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
            fn() => $manager
                ->repository(User::class)
                ->effect(
                    Effect::optional('billingAddress')->properties(
                        Effect::property('value')->assign($this->newAddress),
                    ),
                    $specification,
                )
                ->either(),
        );

        $manager
            ->repository(User::class)
            ->matching($specification)
            ->foreach(
                fn($user) => $assert->same(
                    $this->newAddress,
                    $user->billingAddress()->match(
                        static fn($address) => $address->toString(),
                        static fn() => null,
                    ),
                ),
            );

        $manager
            ->repository(User::class)
            ->matching($specification->not())
            ->foreach(
                fn($user) => $assert
                    ->expected($user->billingAddress()->match(
                        static fn($address) => $address,
                        static fn() => null,
                    ))
                    ->not()
                    ->same($this->newAddress),
            );

        return $manager;
    }
}
