<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Contains,
    Type,
    Types,
    Type\PointInTimeType\Format,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
};
use Innmind\Type\{
    Type as Concrete,
    ClassName,
};
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 * @implements Type<PointInTime>
 */
final class PointInTimeType implements Type
{
    private Clock $clock;
    private Format $format;

    private function __construct(Clock $clock, Format $format)
    {
        $this->clock = $clock;
        $this->format = $format;
    }

    /**
     * @psalm-pure
     */
    public static function new(Clock $clock): self
    {
        return new self($clock, Format::new());
    }

    /**
     * @psalm-pure
     * @deprecated Use ::new() instead
     *
     * @return callable(Types, Concrete, ?Contains): Maybe<self>
     */
    public static function of(Clock $clock): callable
    {
        return static fn(Types $types, Concrete $type) => Maybe::just($type)
            ->filter(static fn($type) => $type->accepts(ClassName::of(PointInTime::class)))
            ->map(static fn() => new self($clock, new Format));
    }

    public function normalize(mixed $value): null|string|int|float|bool
    {
        return $value->format($this->format);
    }

    public function denormalize(null|string|int|float|bool $value): mixed
    {
        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        return $this
            ->clock
            ->at($value, $this->format)
            ->match(
                static fn($point) => $point,
                static fn() => throw new \LogicException("'$value' is not a date"),
            );
    }
}
