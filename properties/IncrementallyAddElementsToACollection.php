<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
    Definition\Type\PointInTimeType\Format,
};
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
final class IncrementallyAddElementsToACollection implements Property
{
    private $createdAt;
    private array $addresses;

    private function __construct(
        $createdAt,
        array $addresses,
    ) {
        $this->createdAt = $createdAt;
        $this->addresses = $addresses;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set\Sequence::of(
                Set\Strings::madeOf(Set\Chars::alphanumerical()),
            )->atLeast(1),
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
            static fn() => Either::right($repository->put($user)),
        );
        $id = $user->id()->toString();
        unset($user); // to make sure there is no in memory cache somewhere

        foreach ($this->addresses as $index => $address) {
            $manager->transactional(
                static function() use ($assert, $repository, $index, $address, $id) {
                    $user = $repository->get(Id::of(User::class, $id))->match(
                        static fn($user) => $user,
                        static fn() => null,
                    );

                    $assert->not()->null($user);
                    $assert->count(
                        $index,
                        $user->addresses(),
                        'Previous addresses have been lost',
                    );
                    $repository->put($user->addAddress($address));

                    return Either::right(null);
                },
            );
        }

        return $manager;
    }
}
