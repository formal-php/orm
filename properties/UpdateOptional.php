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
use Fixtures\Innmind\Time\Point;

/**
 * @implements Property<Manager>
 */
final class UpdateOptional implements Property
{
    private string $name;
    private string $address;
    private $createdAt;

    private function __construct(string $name, string $address, $createdAt)
    {
        $this->name = $name;
        $this->address = $address;
        $this->createdAt = $createdAt;
    }

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::strings()->madeOf(Set::strings()->chars()->alphanumerical()),
            Set::strings()->madeOf(Set::strings()->chars()->alphanumerical()),
            Point::any(),
        );
    }

    public function applicableTo(object $manager): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $manager): object
    {
        $repository = $manager->repository(User::class);
        $user = User::new($this->createdAt, $this->name);

        $_ = $manager->transactional(
            static fn() => $repository
                ->put($user)
                ->either(),
        );
        $id = $user->id()->toString();
        unset($user); // to make sure there is no in memory cache somewhere

        $loaded = $repository
            ->get(Id::of(User::class, $id))
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert->not()->null($loaded);

        $user = $loaded->changeBillingAddress($this->address);

        $_ = $manager->transactional(
            static fn() => $repository
                ->put($user)
                ->either(),
        );
        // free memory to make sure we reload from the database and not the cache
        unset($user);
        unset($loaded);

        $reloaded = $repository
            ->get(Id::of(User::class, $id))
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert->not()->null($reloaded);
        $assert
            ->expected($this->address)
            ->same($reloaded->billingAddress()->match(
                static fn($address) => $address->toString(),
                static fn() => null,
            ));
        $assert
            ->expected($this->name)
            ->same($reloaded->name())
            ->same($reloaded->nameStr()->toString());
        $assert
            ->expected(
                $this
                    ->createdAt
                    ->changeOffset(Offset::utc())
                    ->format(Formats::default),
            )
            ->same(
                $reloaded
                    ->createdAt()
                    ->changeOffset(Offset::utc())
                    ->format(Formats::default),
            );

        $user = $reloaded->removeBillingAddress();

        $_ = $manager->transactional(
            static fn() => $repository
                ->put($user)
                ->either(),
        );
        // free memory to make sure we reload from the database and not the cache
        unset($user);
        unset($reloaded);

        $reloaded = $repository
            ->get(Id::of(User::class, $id))
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert->not()->null($reloaded);
        $assert->null($reloaded->billingAddress()->match(
            static fn($address) => $address->toString(),
            static fn() => null,
        ));
        $assert
            ->expected($this->name)
            ->same($reloaded->name())
            ->same($reloaded->nameStr()->toString());
        $assert
            ->expected(
                $this
                    ->createdAt
                    ->changeOffset(Offset::utc())
                    ->format(Formats::default),
            )
            ->same(
                $reloaded
                    ->createdAt()
                    ->changeOffset(Offset::utc())
                    ->format(Formats::default),
            );

        return $manager;
    }
}
