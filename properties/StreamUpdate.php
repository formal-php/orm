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
        return Set::strings()
            ->madeOf(Set::strings()->chars()->alphanumerical())
            ->map(static fn($name) => new self($name));
    }

    public function applicableTo(object $manager): bool
    {
        return $manager->repository(User::class)->any();
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $manager->transactional(
            fn() => $manager
                ->repository(User::class)
                ->all()
                ->map(fn($user) => $user->rename($this->name))
                ->sink(null)
                ->attempt(
                    static fn($_, $user) => $manager
                        ->repository(User::class)
                        ->put($user),
                )
                ->either(),
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
