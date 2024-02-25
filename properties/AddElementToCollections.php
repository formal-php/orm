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

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set\Strings::madeOf(Set\Chars::alphanumerical()),
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
            fn() => Either::right(
                $repository
                    ->all()
                    ->map(fn($user) => $user->addAddress($this->address))
                    ->foreach($repository->put(...)),
            ),
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
