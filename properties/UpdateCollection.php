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
use Innmind\Immutable\Either;
use Fixtures\Innmind\TimeContinuum\Earth\PointInTime;

/**
 * @implements Property<Manager>
 */
final class UpdateCollection implements Property
{
    private string $name;
    private string $address1;
    private string $address2;
    private string $address3;
    private $createdAt;

    private function __construct(
        string $name,
        string $address1,
        string $address2,
        string $address3,
        $createdAt,
    ) {
        $this->name = $name;
        $this->address1 = $address1;
        $this->address2 = $address2;
        $this->address3 = $address3;
        $this->createdAt = $createdAt;
    }

    public static function any(): Set
    {
        return Set\Composite::immutable(
            static fn(...$args) => new self(...$args),
            Set\Strings::madeOf(Set\Chars::alphanumerical()),
            Set\Strings::madeOf(Set\Chars::alphanumerical()),
            Set\Strings::madeOf(Set\Chars::alphanumerical()),
            Set\Strings::madeOf(Set\Chars::alphanumerical()),
            PointInTime::any(),
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

        $manager->transactional(
            static fn() => Either::right($repository->put($user)),
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
        $assert->true($loaded->addresses()->empty());

        $user = $loaded
            ->addAddress($this->address1)
            ->addAddress($this->address2)
            ->addAddress($this->address3);

        $manager->transactional(
            static fn() => Either::right($repository->put($user)),
        );

        $reloaded = $repository
            ->get(Id::of(User::class, $id))
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert->not()->null($reloaded);
        $addresses = $reloaded
            ->addresses()
            ->map(static fn($address) => $address->toString())
            ->toList();
        $assert
            ->expected($this->address1)
            ->in($addresses);
        $assert
            ->expected($this->address2)
            ->in($addresses);
        $assert
            ->expected($this->address3)
            ->in($addresses);
        $assert
            ->expected($this->name)
            ->same($reloaded->name())
            ->same($reloaded->nameStr()->toString());
        $assert
            ->expected(
                $this
                    ->createdAt
                    ->changeTimezone(new UTC)
                    ->format(new Format),
            )
            ->same(
                $reloaded
                    ->createdAt()
                    ->changeTimezone(new UTC)
                    ->format(new Format),
            );

        $user = $reloaded->removeAddress($this->address2);

        $manager->transactional(
            static fn() => Either::right($repository->put($user)),
        );

        $reloaded = $repository
            ->get(Id::of(User::class, $id))
            ->match(
                static fn($user) => $user,
                static fn() => null,
            );
        $assert->not()->null($reloaded);
        $addresses = $reloaded
            ->addresses()
            ->map(static fn($address) => $address->toString())
            ->toList();
        $assert
            ->expected($this->address1)
            ->in($addresses);
        $assert
            ->expected($this->address2)
            ->not()
            ->in($addresses);
        $assert
            ->expected($this->address3)
            ->in($addresses);
        $assert
            ->expected($this->name)
            ->same($reloaded->name())
            ->same($reloaded->nameStr()->toString());
        $assert
            ->expected(
                $this
                    ->createdAt
                    ->changeTimezone(new UTC)
                    ->format(new Format),
            )
            ->same(
                $reloaded
                    ->createdAt()
                    ->changeTimezone(new UTC)
                    ->format(new Format),
            );

        return $manager;
    }
}
