<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\Manager;
use Fixtures\Formal\ORM\User;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Fixtures\Innmind\TimeContinuum\PointInTime;

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

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->between(0, 100),
            Set::integers()->between(1, 1_000_000), // upper limit to avoid PHP switching the type to float
            Set::integers()->between(0, 1_000_000), // upper limit to avoid PHP switching the type to float
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
            static fn() => $repository->put($user)->either(),
        );

        $found = $repository
            ->all()
            ->take($this->take)
            ->drop($this->take + $this->drop)
            ->sequence();

        $assert
            ->expected(0)
            ->same($found->size());

        return $manager;
    }
}
