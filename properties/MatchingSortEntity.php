<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Sort,
};
use Fixtures\Formal\ORM\{
    User,
    MainAddress,
};
use Innmind\Specification\Sign;
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
final class MatchingSortEntity implements Property
{
    private $createdAt;
    private string $prefix;
    private string $address1;
    private string $address2;

    private function __construct(
        $createdAt,
        string $prefix,
        string $address1,
        string $address2,
    ) {
        $this->createdAt = $createdAt;
        $this->prefix = $prefix;
        $this->address1 = $address1;
        $this->address2 = $address2;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set\Strings::madeOf(Set\Chars::alphanumerical())->between(10, 100),
            Set\Strings::madeOf(Set\Chars::alphanumerical())->between(10, 100),
            Set\Strings::madeOf(Set\Chars::alphanumerical())->between(10, 100),
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $user1 = User::new($this->createdAt)->changeAddress($this->prefix.'a'.$this->address1);
        $user2 = User::new($this->createdAt)->changeAddress($this->prefix.'b'.$this->address2);

        $repository = $manager->repository(User::class);
        $manager->transactional(
            static function() use ($repository, $user1, $user2) {
                $repository->put($user1);
                $repository->put($user2);

                return Either::right(null);
            },
        );

        $found = $repository
            ->matching(MainAddress::of(
                Sign::startsWith,
                $this->prefix,
            ))
            ->sort('mainAddress.value', Sort::asc)
            ->fetch()
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected([
                $user1->id()->toString(),
                $user2->id()->toString(),
            ])
            ->same($found);

        $found = $repository
            ->matching(MainAddress::of(
                Sign::startsWith,
                $this->prefix,
            ))
            ->sort('mainAddress.value', Sort::desc)
            ->fetch()
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected([
                $user2->id()->toString(),
                $user1->id()->toString(),
            ])
            ->same($found);

        return $manager;
    }
}
