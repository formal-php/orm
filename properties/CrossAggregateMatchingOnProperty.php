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
use Fixtures\Innmind\TimeContinuum\PointInTime;

/**
 * @implements Property<Manager>
 */
final class CrossAggregateMatchingOnProperty implements Property
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

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            PointInTime::any(),
            Set\MutuallyExclusive::of(
                Set::strings()
                    ->madeOf(Set::strings()->chars()->alphanumerical())
                    ->between(10, 100),
                Set::strings()
                    ->madeOf(Set::strings()->chars()->alphanumerical())
                    ->between(10, 100),
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
                $repository->put($child1)->unwrap();
                $repository->put($child2)->unwrap();
                $repository->put($parent1)->unwrap();
                $repository->put($parent2)->unwrap();

                return Either::right(null);
            },
        );

        // TODO this is not optimal to specify the id property as it's the
        // default behaviour of cross aggregate matching. However using another
        // property that is always an Id would require new fixtures classes.
        // Since this is an experimental feature this will do for now. But once
        // the experimental flag is removed, new fixtures need to be added.
        $found = $repository
            ->matching(Just::of(
                'sibling',
                Comparator\Property::of(
                    'id',
                    Sign::in,
                    $repository
                        ->matching(Username::of(
                            Sign::equality,
                            Str::of($this->name1),
                        ))
                        ->property('id'),
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
                    $repository
                        ->matching(Username::of(
                            Sign::equality,
                            Str::of($this->name2),
                        ))
                        ->property('id'),
                ),
            ))
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert->count(0, $found);

        // this allows to check the cross match on aggregate properties
        $found = $repository
            ->matching(Comparator\Property::of(
                'id',
                Sign::in,
                $repository
                    ->matching(
                        Comparator\Property::of('id', Sign::equality, $child1->id())->or(
                            Comparator\Property::of('id', Sign::equality, $parent2->id()),
                        ),
                    )
                    ->property('id'),
            ))
            ->map(static fn($user) => $user->id()->toString())
            ->toList();

        $assert
            ->expected($child1->id()->toString())
            ->in($found);
        $assert
            ->expected($parent2->id()->toString())
            ->in($found);
        $assert
            ->expected($child2->id()->toString())
            ->not()
            ->in($found);
        $assert
            ->expected($parent1->id()->toString())
            ->not()
            ->in($found);

        return $manager;
    }
}
