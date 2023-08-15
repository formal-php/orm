<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
    Definition\Type\PointInTimeType\Format,
};
use Fixtures\Formal\ORM\User;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Innmind\TimeContinuum\Earth\Timezone\UTC;
use Innmind\Immutable\{
    Str,
    Either,
};
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class AddAggregate implements Property
{
    private ?string $name;
    private $createdAt;

    private function __construct(?string $name, $createdAt)
    {
        $this->name = $name;
        $this->createdAt = $createdAt;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Nullable::of(Set\Strings::madeOf(Set\Chars::alphanumerical())),
            PointInTime::any(),
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $current = $manager
            ->repository(User::class)
            ->all()
            ->sequence()
            ->size();
        $user = User::new($this->createdAt, $this->name);
        $manager->transactional(
            static fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->put($user),
            ),
        );
        $id = $user->id()->toString();
        unset($user); // to make sure there is no in memory cache somewhere

        $assert
            ->expected($current + 1)
            ->same(
                $manager
                    ->repository(User::class)
                    ->all()
                    ->sequence()
                    ->size(),
            );
        $assert
            ->expected(1)
            ->same(
                $manager
                    ->repository(User::class)
                    ->all()
                    ->sequence()
                    ->filter(static fn($user) => $user->id()->toString() === $id)
                    ->size(),
            );

        $fetched = $manager
            ->repository(User::class)
            ->get(Id::of(User::class, $id))
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert
            ->not()
            ->null($fetched);
        $assert
            ->expected($id)
            ->same($fetched->id()->toString());
        $assert
            ->expected($this->name)
            ->same($fetched->name());
        $assert
            ->expected(
                $this
                    ->createdAt
                    ->changeTimezone(new UTC)
                    ->format(new Format),
            )
            ->same(
                $fetched
                    ->createdAt()
                    ->changeTimezone(new UTC)
                    ->format(new Format),
            );

        if (!\is_null($fetched->name())) {
            $str = $fetched->nameStr();
            $assert
                ->object($str)
                ->instance(Str::class);
            $assert
                ->expected($this->name)
                ->same($str->toString());
        }

        return $manager;
    }
}
