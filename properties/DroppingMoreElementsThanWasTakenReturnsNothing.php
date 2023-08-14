<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Sort,
};
use Fixtures\Formal\ORM\User;
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
final class DroppingMoreElementsThanWasTakenReturnsNothing implements Property
{
    private $createdAt;
    private string $name;
    private int $take;
    private int $drop;

    private function __construct(
        $createdAt,
        string $name,
        int $take,
        int $drop,
    ) {
        $this->createdAt = $createdAt;
        $this->name = $name;
        $this->take = $take;
        $this->drop = $drop;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set\Strings::madeOf(Set\Chars::alphanumerical())->between(0, 100),
            Set\Integers::between(1, 1_000_000), // upper limit to avoid PHP switching the type to float
            Set\Integers::between(0, 1_000_000), // upper limit to avoid PHP switching the type to float
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
            static function() use ($repository, $user) {
                $repository->put($user);

                return Either::right(null);
            },
        );

        $found = $repository
            ->all()
            ->take($this->take)
            ->drop($this->take + $this->drop)
            ->fetch();

        $assert
            ->expected(0)
            ->same($found->size());

        return $manager;
    }
}
