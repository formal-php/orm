<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Specification\Just,
};
use Fixtures\Formal\ORM\{
    User,
    Username,
};
use Innmind\Specification\{
    Comparator,
    Sign,
};
use Innmind\Immutable\{
    Str,
    Either,
};
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class CrossAggregateSearch implements Property
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
        $child1 = User::new($this->createdAt, $this->name1);
        $child2 = User::new($this->createdAt, $this->name2);
        $parent1 = User::new($this->createdAt)->isSiblingOf($child1->id());
        $parent2 = User::new($this->createdAt)->isSiblingOf($child1->id());

        $repository = $manager->repository(User::class);
        $manager->transactional(
            static function() use ($repository, $child1, $child2, $parent1, $parent2) {
                $repository->put($child1);
                $repository->put($child2);
                $repository->put($parent1);
                $repository->put($parent2);

                return Either::right(null);
            },
        );

        $found = $repository
            ->matching(Just::of(
                'sibling',
                Comparator\Property::of(
                    'id',
                    Sign::in,
                    $repository->matching(Username::of(
                        Sign::equality,
                        Str::of($this->name1),
                    )),
                ),
            ))
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($parent1->id()->toString())
            ->in($found);
        $assert
            ->expected($parent2->id()->toString())
            ->in($found);
        $assert
            ->expected($child1->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($child2->id()->toString())
            ->not()
            ->in($found);

        $found = $repository
            ->matching(Just::of(
                'sibling',
                Comparator\Property::of(
                    'id',
                    Sign::in,
                    $repository->matching(Username::of(
                        Sign::equality,
                        Str::of($this->name2),
                    )),
                ),
            ))
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert->count(0, $found);

        return $manager;
    }
}
