<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
};
use Fixtures\Formal\ORM\{
    User,
    Username,
};
use Innmind\Specification\Sign;
use Innmind\Immutable\Str;
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
final class RemoveSpecification implements Property
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
        $user1 = User::new($this->createdAt, $this->name1);
        $user2 = User::new($this->createdAt, $this->name2);

        $repository = $manager->repository(User::class);
        $manager->transactional(
            static function() use ($repository, $user1, $user2) {
                $repository->put($user1);
                $repository->put($user2);

                return Either::right(null);
            },
        );
        $user1Id = $user1->id()->toString();
        $user2Id = $user2->id()->toString();
        unset($user1);
        unset($user2);

        $manager->transactional(
            function() use ($repository) {
                $repository->remove(Username::of(
                    Sign::equality,
                    Str::of($this->name1),
                ));

                return Either::right(null);
            },
        );

        $assert->false($repository->contains(Id::of(User::class, $user1Id)));
        $assert->true($repository->contains(Id::of(User::class, $user2Id)));

        return $manager;
    }
}
