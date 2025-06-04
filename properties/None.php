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
final class None implements Property
{
    private $createdAt;
    private string $prefix;
    private string $name1;
    private string $name2;
    private string $unknown;

    private function __construct(
        $createdAt,
        string $prefix,
        string $name1,
        string $name2,
        string $unknown,
    ) {
        $this->createdAt = $createdAt;
        $this->prefix = $prefix;
        $this->name1 = $name1;
        $this->name2 = $name2;
        $this->unknown = $unknown;
    }

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->between(10, 100),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->between(10, 100),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->between(10, 100),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->between(10, 255), // 10 to avoid collisions
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
                $repository->put($user1)->unwrap();
                $repository->put($user2)->unwrap();
                $repository->put($user3)->unwrap();

                return Either::right(null);
            },
        );

        $assert->false($repository->none(Username::of(
            Sign::equality,
            Str::of($this->name1),
        )));
        $assert->false($repository->none(Username::of(
            Sign::equality,
            Str::of($this->name2),
        )));
        $assert->false($repository->none(Username::of(
            Sign::startsWith,
            Str::of($this->prefix),
        )));
        $assert->false($repository->none(Username::of(
            Sign::endsWith,
            Str::of($this->name1),
        )));
        $assert->true($repository->none(Username::of(
            Sign::contains,
            Str::of($this->unknown),
        )));

        return $manager;
    }
}
