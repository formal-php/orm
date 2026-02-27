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
final class UpdateCollection implements Property
{
    private string $name;
    private $createdAt;
    private string $address1;
    private string $address2;
    private string $address3;

    private function __construct(
        string $name,
        $createdAt,
        string $address1,
        string $address2,
        string $address3,
    ) {
        $this->name = $name;
        $this->createdAt = $createdAt;
        $this->address1 = $address1;
        $this->address2 = $address2;
        $this->address3 = $address3;
    }

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn($name, $createdAt, $addresses) => new self($name, $createdAt, ...$addresses),
            Set::strings()->madeOf(Set::strings()->chars()->alphanumerical()),
            Point::any(),
            Set::compose(
                static fn(...$addresses) => $addresses,
                Set::strings()->madeOf(Set::strings()->chars()->alphanumerical()),
                Set::strings()->madeOf(Set::strings()->chars()->alphanumerical()),
                Set::strings()->madeOf(Set::strings()->chars()->alphanumerical()),
            )->filter(
                static fn($addresses) => $addresses[0] !== $addresses[1] &&
                    $addresses[1] !== $addresses[2] &&
                    $addresses[0] !== $addresses[2],
            ),
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
        $assert->true($loaded->addresses()->empty());

        $user = $loaded
            ->addAddress($this->address1)
            ->addAddress($this->address2)
            ->addAddress($this->address3);

        $_ = $manager->transactional(
            static fn() => $repository
                ->put($user)
                ->either(),
        );

        $reloaded = $repository
            ->get($user->id())
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
                    ->changeOffset(Offset::utc())
                    ->format(Formats::default),
            )
            ->same(
                $reloaded
                    ->createdAt()
                    ->changeOffset(Offset::utc())
                    ->format(Formats::default),
            );

        $user = $reloaded->removeAddress($this->address2);

        $_ = $manager->transactional(
            static fn() => $repository
                ->put($user)
                ->either(),
        );

        $reloaded = $repository
            ->get($user->id())
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
