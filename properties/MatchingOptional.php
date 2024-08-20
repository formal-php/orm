<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Specification\Just,
    Specification\Has,
};
use Fixtures\Formal\ORM\{
    User,
    AddressValue,
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
final class MatchingOptional implements Property
{
    private $createdAt;
    private string $name1;
    private string $name2;

    private function __construct(
        $createdAt,
        array $names,
    ) {
        $this->createdAt = $createdAt;
        [$this->name1, $this->name2] = $names;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set\MutuallyExclusive::of(
                Set\Strings::madeOf(Set\Chars::alphanumerical())->between(10, 100),
                Set\Strings::madeOf(Set\Chars::alphanumerical())->between(10, 100),
            ),
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $user1 = User::new($this->createdAt)->changeBillingAddress($this->name1);
        $user2 = User::new($this->createdAt)->changeBillingAddress($this->name2);
        $user3 = User::new($this->createdAt);

        $repository = $manager->repository(User::class);
        $manager->transactional(
            static function() use ($repository, $user1, $user2, $user3) {
                $repository->put($user1);
                $repository->put($user2);
                $repository->put($user3);

                return Either::right(null);
            },
        );

        $found = $repository
            ->matching(Has::a('billingAddress'))
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($user1->id()->toString())
            ->in($found);
        $assert
            ->expected($user2->id()->toString())
            ->in($found);
        $assert
            ->expected($user3->id()->toString())
            ->not()
            ->in($found);

        $found = $repository
            ->matching(Just::of('billingAddress', AddressValue::of(
                Sign::equality,
                $this->name1,
            )))
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($user1->id()->toString())
            ->in($found);
        $assert
            ->expected($user2->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($user3->id()->toString())
            ->not()
            ->in($found);

        $found = $repository
            ->matching(Just::of('billingAddress', AddressValue::of(
                Sign::equality,
                $this->name2,
            )))
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($user1->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($user2->id()->toString())
            ->in($found);
        $assert
            ->expected($user3->id()->toString())
            ->not()
            ->in($found);

        $found = $repository
            ->matching(Just::of('billingAddress', AddressValue::of(
                Sign::equality,
                $this->name1,
            )->not()))
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($user1->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($user2->id()->toString())
            ->in($found);
        $assert
            ->expected($user3->id()->toString())
            ->not()
            ->in($found);

        return $manager;
    }
}
