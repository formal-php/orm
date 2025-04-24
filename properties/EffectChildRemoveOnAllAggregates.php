<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Effect,
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
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class EffectChildRemoveOnAllAggregates implements Property
{
    private function __construct(
        private ?string $name,
        private string $prefix,
        private string $suffix,
        private Sign $sign,
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
            Set::of(
                Sign::equality,
                Sign::startsWith,
                Sign::endsWith,
                Sign::contains,
                Sign::in,
            ),
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

        $address = $this->prefix.$this->suffix;
        $manager->transactional(
            static fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->effect(
                        Effect::child('addresses')->add(
                            User\Address::new($address),
                        ),
                    ),
            ),
        );

        $manager
            ->repository(User::class)
            ->all()
            ->foreach(
                static fn($user) => $assert
                    ->array(
                        $user
                            ->addresses()
                            ->map(static fn($address) => $address->toString())
                            ->toList(),
                    )
                    ->contains($address),
            );

        $value = match ($this->sign) {
            Sign::equality => $address,
            Sign::startsWith => $this->prefix,
            Sign::endsWith => $this->suffix,
            Sign::contains => $this->suffix,
            Sign::in => [$address],
        };

        $manager->transactional(
            fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->effect(
                        Effect::child('addresses')->remove(
                            Comparator\Property::of(
                                'value',
                                $this->sign,
                                $value,
                            ),
                        ),
                    ),
            ),
        );

        $manager
            ->repository(User::class)
            ->all()
            ->foreach(
                static fn($user) => $assert
                    ->array(
                        $user
                            ->addresses()
                            ->map(static fn($address) => $address->toString())
                            ->toList(),
                    )
                    ->not()
                    ->contains($address),
            );

        return $manager;
    }
}
