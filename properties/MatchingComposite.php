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
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class MatchingComposite implements Property
{
    private $createdAt;
    private string $prefix;
    private string $name1;
    private string $name2;

    private function __construct(
        $createdAt,
        string $prefix,
        string $name1,
        string $name2,
    ) {
        $this->createdAt = $createdAt;
        $this->prefix = $prefix;
        $this->name1 = $name1;
        $this->name2 = $name2;
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
        $user1 = User::new($this->createdAt, $this->name1);
        $user2 = User::new($this->createdAt, $this->name2);
        $user3 = User::new($this->createdAt, $this->prefix.$this->name1);

        $repository = $manager->repository(User::class);
        $repository->put($user1);
        $repository->put($user2);
        $repository->put($user3);

        $found = $repository
            ->matching(
                Username::of(Sign::endsWith, Str::of($this->name1))->and(
                    Username::of(Sign::startsWith, Str::of($this->prefix)),
                ),
            )
            ->fetch()
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($user1->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($user2->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($user3->id()->toString())
            ->in($found);

        $found = $repository
            ->matching(
                Username::of(Sign::equality, Str::of($this->name1))->or(
                    Username::of(Sign::equality, Str::of($this->name2)),
                ),
            )
            ->fetch()
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

        return $manager;
    }
}
