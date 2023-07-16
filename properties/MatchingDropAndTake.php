<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Sort,
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
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class MatchingDropAndTake implements Property
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

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set\Strings::madeOf(Set\Chars::alphanumerical())->between(10, 100),
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
        $repository->put($user1);
        $repository->put($user2);
        $repository->put($user3);

        $found = $repository
            ->matching(Username::of(
                Sign::equality,
                Str::of($this->name),
            ))
            ->drop(1)
            ->take(1)
            ->fetch();

        $assert
            ->expected(1)
            ->same($found->size());

        return $manager;
    }
}
