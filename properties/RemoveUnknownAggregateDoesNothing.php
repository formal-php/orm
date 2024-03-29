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
use Innmind\Immutable\Either;

/**
 * @implements Property<Manager>
 */
final class RemoveUnknownAggregateDoesNothing implements Property
{
    private string $uuid;

    private function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public static function any(): Set
    {
        return Set\Uuid::any()->map(static fn($uuid) => new self($uuid));
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $assert
            ->not()
            ->throws(
                fn() => $manager->transactional(
                    fn() => Either::right(
                        $manager
                            ->repository(User::class)
                            ->remove(Id::of(User::class, $this->uuid)),
                    ),
                ),
            );

        return $manager;
    }
}
