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
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class MatchingExclusion implements Property
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
        $user = User::new($this->createdAt, $this->name);

        $repository = $manager->repository(User::class);
        $manager->transactional(
            static fn() => Either::right($repository->put($user)),
        );

        $found = $repository
            ->matching(
                Username::of(Sign::equality, Str::of($this->name))->not(),
            )
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($user->id()->toString())
            ->not()
            ->in($found);

        return $manager;
    }
}
