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
final class SizeWithSpecification implements Property
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
        $manager->transactional(
            static function() use ($repository, $user1, $user2, $user3) {
                $repository->put($user1);
                $repository->put($user2);
                $repository->put($user3);

                return Either::right(null);
            },
        );

        $found = $repository->size(Username::of(
            Sign::equality,
            Str::of($this->name1),
        ));

        $assert
            ->expected(1)
            ->same($found);

        $found = $repository->size(Username::of(
            Sign::equality,
            Str::of($this->name2),
        ));

        $assert
            ->expected(1)
            ->same($found);

        $found = $repository->size(Username::of(
            Sign::startsWith,
            Str::of($this->prefix),
        ));

        $assert
            ->expected(1)
            ->same($found);

        $found = $repository->size(Username::of(
            Sign::endsWith,
            Str::of($this->name1),
        ));

        $assert
            ->expected(2)
            ->same($found);

        return $manager;
    }
}
