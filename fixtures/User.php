<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Formal\ORM\{
    Id,
    Definition\Template,
};
use Innmind\TimeContinuum\PointInTime;
use Innmind\Immutable\Str;

final class User
{
    /** @var Id<self> */
    #[Template(self::class)]
    private Id $id;
    private PointInTime $createdAt;
    private ?string $name;
    private ?Str $nameStr;

    private function __construct(PointInTime $createdAt, ?string $name)
    {
        $this->id = Id::new(self::class);
        $this->createdAt = $createdAt;
        $this->name = $name;
        $this->nameStr = match ($name) {
            null => null,
            default => Str::of($name),
        };
    }

    public static function new(
        PointInTime $createdAt,
        string $name = null,
    ): self {
        return new self($createdAt, $name);
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
        return $this->nameStr;
    }
}
