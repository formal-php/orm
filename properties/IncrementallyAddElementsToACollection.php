<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
};
use Fixtures\Formal\ORM\User;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Fixtures\Innmind\Time\Point;

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

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Point::any(),
            Set::sequence(
                Set::strings()->madeOf(Set::strings()->chars()->alphanumerical()),
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
            static fn() => $repository->put($user)->either(),
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
                    $assert->same(
                        $index,
                        $user->addresses()->size(),
                        'Previous addresses have been lost',
                    );

                    return $repository
                        ->put($user->addAddress($address))
                        ->either();
                },
            );
        }

        return $manager;
    }
}
