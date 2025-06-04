<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\Manager;
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
use Fixtures\Innmind\TimeContinuum\PointInTime;

/**
 * @implements Property<Manager>
 */
final class MatchingDrop implements Property
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
        $user1 = User::new($this->createdAt, $this->name);
        $user2 = User::new($this->createdAt, $this->name);
        $user3 = User::new($this->createdAt, $this->name);

        $repository = $manager->repository(User::class);
        $manager->transactional(
            static function() use ($repository, $user1, $user2, $user3) {
                $repository->put($user1)->unwrap();
                $repository->put($user2)->unwrap();
                $repository->put($user3)->unwrap();

                return Either::right(null);
            },
        );

        $found = $repository
            ->matching(Username::of(
                Sign::equality,
                Str::of($this->name),
            ))
            ->drop(4)
            ->sequence();

        $assert
            ->expected(0)
            ->same($found->size());

        $found = $repository
            ->matching(Username::of(
                Sign::equality,
                Str::of($this->name),
            ))
            ->drop(2)
            ->sequence();

        $assert
            ->expected(1)
            ->same($found->size());

        $found = $repository
            ->matching(Username::of(
                Sign::equality,
                Str::of($this->name),
            ))
            ->drop(1)
            ->drop(1)
            ->sequence();

        $assert
            ->expected(1)
            ->same(
                $found->size(),
                'drop() must be cumulative',
            );

        return $manager;
    }
}
