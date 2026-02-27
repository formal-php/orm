<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
    Definition\Type\PointInTimeType\Formats,
};
use Fixtures\Formal\ORM\User;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Innmind\Time\Offset;
use Innmind\Immutable\Str;
use Fixtures\Innmind\Time\Point;

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

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::strings()
                ->madeOf(Set::strings()->chars()->alphanumerical())
                ->toSet()
                ->nullable(),
            Point::any(),
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
            ->size();
        $user = User::new($this->createdAt, $this->name);
        $manager->transactional(
            static fn() => $manager
                ->repository(User::class)
                ->put($user)
                ->either(),
        );
        $id = $user->id()->toString();
        unset($user); // to make sure there is no in memory cache somewhere

        $assert
            ->expected($current + 1)
            ->same(
                $manager
                    ->repository(User::class)
                    ->size(),
            );
        $assert
            ->expected(1)
            ->same(
                $manager
                    ->repository(User::class)
                    ->all()
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
                    ->changeOffset(Offset::utc())
                    ->format(Formats::default),
            )
            ->same(
                $fetched
                    ->createdAt()
                    ->changeOffset(Offset::utc())
                    ->format(Formats::default),
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
