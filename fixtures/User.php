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

    /**
     * @param Id<self> $id
     */
    private function __construct(Id $id, PointInTime $createdAt, ?string $name)
    {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->name = $name;
        $this->nameStr = Maybe::of($name)->map(Str::of(...));
    }

    public static function new(
        PointInTime $createdAt,
        string $name = null,
    ): self {
        return new self(Id::new(self::class), $createdAt, $name);
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

    public function rename(string $name): self
    {
        return new self(
            $this->id,
            $this->createdAt,
            $name,
        );
    }
}
