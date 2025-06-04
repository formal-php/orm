<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\Manager;
use Fixtures\Formal\ORM\User;
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Fixtures\Innmind\TimeContinuum\PointInTime;

/**
 * @implements Property<Manager>
 */
final class AddElementToCollections implements Property
{
    private $createdAt;
    private string $address;

    private function __construct(
        $createdAt,
        string $address,
    ) {
        $this->createdAt = $createdAt;
        $this->address = $address;
    }

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set::strings()->madeOf(Set::strings()->chars()->alphanumerical()),
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $repository = $manager->repository(User::class);
        $user = User::new($this->createdAt);

        $manager->transactional(
            fn() => $repository
                ->all()
                ->map(fn($user) => $user->addAddress($this->address))
                ->sink(SideEffect::identity())
                ->attempt(static fn($_, $user) => $repository->put($user))
                ->either(),
        );

        $repository
            ->all()
            ->foreach(fn($user) => $assert->true(
                $user
                    ->addresses()
                    ->map(static fn($address) => $address->toString())
                    ->contains($this->address),
            ));

        return $manager;
    }
}
