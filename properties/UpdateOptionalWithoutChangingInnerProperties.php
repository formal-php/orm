<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\Manager;
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
final class UpdateOptionalWithoutChangingInnerProperties implements Property
{
    private $createdAt;
    private string $name;

    private function __construct(
        $createdAt,
        string $name,
    ) {
        $this->createdAt = $createdAt;
        $this->name = $name;
    }

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->between(10, 100),
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $user = User::new($this->createdAt)->changeBillingAddress($this->name);

        $repository = $manager->repository(User::class);
        $manager->transactional(
            static function() use ($repository, $user) {
                $repository->put($user);

                return Either::right(null);
            },
        );

        $user = $user->mapBillingAddress(static fn($address) => clone $address);

        $assert->not()->throws(
            static fn() => $manager->transactional(
                static function() use ($repository, $user) {
                    $repository->put($user);

                    return Either::right(null);
                },
            ),
        );

        return $manager;
    }
}
