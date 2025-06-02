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
use Fixtures\Innmind\TimeContinuum\PointInTime;

/**
 * @implements Property<Manager>
 */
final class EffectOptionalNothingOnAllAggregates implements Property
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

        $manager->transactional(
            static fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->effect(
                        Effect::optional('billingAddress')->nothing(),
                    ),
            ),
        );

        $manager
            ->repository(User::class)
            ->all()
            ->foreach(
                static fn($user) => $assert->null(
                    $user->billingAddress()->match(
                        static fn($address) => $address,
                        static fn() => null,
                    ),
                ),
            );

        return $manager;
    }
}
