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
use Innmind\Immutable\Either;

/**
 * @implements Property<Manager>
 */
final class StreamUpdate implements Property
{
    private string $name;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function any(): Set
    {
        return Set\Strings::madeOf(Set\Chars::alphanumerical())->map(static fn($name) => new self($name));
    }

    public function applicableTo(object $manager): bool
    {
        return $manager->repository(User::class)->any();
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $manager->transactional(
            fn() => Either::right(
                $manager
                    ->repository(User::class)
                    ->all()
                    ->map(fn($user) => $user->rename($this->name))
                    ->foreach(
                        $manager
                            ->repository(User::class)
                            ->put(...),
                    ),
            ),
        );

        $_ = $manager
            ->repository(User::class)
            ->all()
            ->foreach(
                fn($user) => $assert
                    ->expected($this->name)
                    ->same($user->name()),
            );

        return $manager;
    }
}
