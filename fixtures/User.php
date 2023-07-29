<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Formal\ORM\{
    Id,
    Definition\Template,
};
use Innmind\TimeContinuum\PointInTime;
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class User
{
    /** @var Id<self> */
    #[Template(self::class)]
    private Id $id;
    private PointInTime $createdAt;
    private ?string $name;
    /** @var Maybe<Str> */
    #[Template(Str::class)]
    private Maybe $nameStr;
    private User\Address $mainAddress;
    /** @var Maybe<User\Address> */
    #[Template(User\Address::class)]
    private Maybe $billingAddress;

    /**
     * @param Id<self> $id
     * @param Maybe<User\Address> $billingAddress
     */
    private function __construct(
        Id $id,
        PointInTime $createdAt,
        ?string $name,
        User\Address $mainAddress,
        Maybe $billingAddress,
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->name = $name;
        $this->nameStr = Maybe::of($name)->map(Str::of(...));
        $this->mainAddress = $mainAddress;
        $this->billingAddress = $billingAddress;
    }

    public static function new(
        PointInTime $createdAt,
        string $name = null,
    ): self {
        /** @var Maybe<User\Address> */
        $billingAddress = Maybe::nothing();

        return new self(
            Id::new(self::class),
            $createdAt,
            $name,
            User\Address::new('nowhere'),
            $billingAddress,
        );
    }

    public function id(): Id
    {
        return $this->id;
    }

    public function createdAt(): PointInTime
    {
        return $this->createdAt;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function nameStr(): ?Str
    {
        return $this->nameStr->match(
            static fn($str) => $str,
            static fn() => null,
        );
    }

    public function mainAddress(): User\Address
    {
        return $this->mainAddress;
    }

    /**
     * @return Maybe<User\Address>
     */
    public function billingAddress(): Maybe
    {
        return $this->billingAddress;
    }

    public function rename(string $name): self
    {
        return new self(
            $this->id,
            $this->createdAt,
            $name,
            $this->mainAddress,
            $this->billingAddress,
        );
    }

    public function changeAddress(string $address): self
    {
        return new self(
            $this->id,
            $this->createdAt,
            $this->name,
            User\Address::new($address),
            $this->billingAddress,
        );
    }

    public function changeBillingAddress(string $address): self
    {
        return new self(
            $this->id,
            $this->createdAt,
            $this->name,
            $this->mainAddress,
            Maybe::just(User\Address::new($address)),
        );
    }

    public function removeBillingAddress(): self
    {
        /** @var Maybe<User\Address> */
        $billingAddress = Maybe::nothing();

        return new self(
            $this->id,
            $this->createdAt,
            $this->name,
            $this->mainAddress,
            $billingAddress,
        );
    }
}
