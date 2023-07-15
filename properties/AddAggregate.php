<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Formal\ORM\{
    Manager,
    Id,
};
use Fixtures\Formal\ORM\User;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};
use Innmind\Immutable\Str;

/**
 * @implements Property<Manager>
 */
final class AddAggregate implements Property
{
    private ?string $name;

    private function __construct(?string $name)
    {
        $this->name = $name;
    }

    public static function any(): Set
    {
        return Set\Nullable::of(Set\Strings::madeOf(Set\Chars::alphanumerical()))->map(
            static fn($name) => new self($name),
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
            ->size();
        $manager
            ->repository(User::class)
            ->put($user = User::new($this->name));
        $id = $user->id()->toString();
        unset($user); // to make sure there is no in memory cache somewhere

        $assert
            ->expected($current + 1)
            ->same(
                $manager
                    ->repository(User::class)
                    ->all()
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
