<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Formal\ORM\{
    Id,
    Definition\Contains,
};
use Innmind\TimeContinuum\PointInTime;
use Innmind\Immutable\{
    Str,
    Maybe,
    Set,
};

final class User
{
    /** @var Id<self> */
    private Id $id;
    private PointInTime $createdAt;
    private ?string $name;
    /** @var Maybe<Str> */
    #[Contains(Str::class)]
    private Maybe $nameStr;
    private User\Address $mainAddress;
    /** @var Maybe<User\Address> */
    #[Contains(User\Address::class)]
    private Maybe $billingAddress;
    /** @var Set<User\Address> */
    #[Contains(User\Address::class)]
    private Set $addresses;
    /** @var Maybe<Role> */
    #[Contains(Role::class)]
    private Maybe $role;
    /** @var Set<Role> */
    #[Contains(Role::class)]
    private Set $roles;

    /**
     * @param Id<self> $id
     * @param Maybe<User\Address> $billingAddress
     * @param Set<User\Address> $addresses
     * @param Maybe<Role> $role
     * @param Set<Role> $roles
     */
    private function __construct(
        Id $id,
        PointInTime $createdAt,
        ?string $name,
        User\Address $mainAddress,
        Maybe $billingAddress,
        Set $addresses,
        Maybe $role,
        Set $roles,
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->name = $name;
        $this->nameStr = Maybe::of($name)->map(Str::of(...));
        $this->mainAddress = $mainAddress;
        $this->billingAddress = $billingAddress;
        $this->addresses = $addresses;
        $this->role = $role;
        $this->roles = $roles;
    }

    public static function new(
        PointInTime $createdAt,
        string $name = null,
    ): self {
        /** @var Maybe<User\Address> */
        $billingAddress = Maybe::nothing();
        /** @var Maybe<Role> */
        $role = Maybe::nothing();

        return new self(
            Id::new(self::class),
            $createdAt,
            $name,
            User\Address::new('nowhere'),
            $billingAddress,
            Set::of(),
            $role,
            Set::of(),
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

    /**
     * @return Set<User\Address>
     */
    public function addresses(): Set
    {
        return $this->addresses;
    }

    /**
     * @return Maybe<Role>
     */
    public function role(): Maybe
    {
        return $this->role;
    }

    /**
     * @return Set<Role>
     */
    public function roles(): Set
    {
        return $this->roles;
    }

    public function rename(string $name): self
    {
        return new self(
            $this->id,
            $this->createdAt,
            $name,
            $this->mainAddress,
            $this->billingAddress,
            $this->addresses,
            $this->role,
            $this->roles,
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
            $this->addresses,
            $this->role,
            $this->roles,
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
            $this->addresses,
            $this->role,
            $this->roles,
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
            $this->addresses,
            $this->role,
            $this->roles,
        );
    }

    public function addAddress(string $address): self
    {
        return new self(
            $this->id,
            $this->createdAt,
            $this->name,
            $this->mainAddress,
            $this->billingAddress,
            ($this->addresses)(User\Address::new($address)),
            $this->role,
            $this->roles,
        );
    }

    public function removeAddress(string $address): self
    {
        return new self(
            $this->id,
            $this->createdAt,
            $this->name,
            $this->mainAddress,
            $this->billingAddress,
            $this->addresses->filter(static fn($existing) => $existing->toString() !== $address),
            $this->role,
            $this->roles,
        );
    }

    public function useRole(Role $role): self
    {
        return new self(
            $this->id,
            $this->createdAt,
            $this->name,
            $this->mainAddress,
            $this->billingAddress,
            $this->addresses,
            Maybe::just($role),
            $this->roles,
        );
    }

    public function useRoles(Role ...$roles): self
    {
        return new self(
            $this->id,
            $this->createdAt,
            $this->name,
            $this->mainAddress,
            $this->billingAddress,
            $this->addresses,
            $this->role,
            Set::of(...$roles),
        );
    }
}
