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
use Fixtures\Innmind\TimeContinuum\PointInTime;

/**
 * @implements Property<Manager>
 */
final class EffectChildAddOnAllAggregates implements Property
{
    private function __construct(
        private ?string $name,
        private string $address,
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
            static fn() => $manager
                ->repository(User::class)
                ->put($user)
                ->either(),
        );
        unset($user); // to make sure there is no in memory cache somewhere

        $manager->transactional(
            fn() => $manager
                ->repository(User::class)
                ->effect(
                    Effect::collection('addresses')->add(
                        User\Address::new($this->address),
                    ),
                )
                ->either(),
        );

        $manager
            ->repository(User::class)
            ->all()
            ->foreach(
                fn($user) => $assert
                    ->array(
                        $user
                            ->addresses()
                            ->map(static fn($address) => $address->toString())
                            ->toList(),
                    )
                    ->contains($this->address),
            );

        return $manager;
    }
}
