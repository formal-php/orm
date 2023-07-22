<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
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
    Nullable,
    ClassName,
};
use Innmind\Immutable\Maybe;

/**
 * @implements Type<PointInTime>
 */
final class PointInTimeType implements Type
{
    private Clock $clock;

    private function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    /**
     * @return callable(Types, Concrete): Maybe<self>
     */
    public static function of(Clock $clock): callable
    {
        return static fn(Types $types, Concrete $type) => Maybe::just($type)
            ->filter(static fn($type) => $type->accepts(ClassName::of(PointInTime::class)))
            ->map(static fn() => new self($clock));
    }

    public function normalize(mixed $value): null|string|int|bool
    {
        return $value->format(new Format);
    }

    public function denormalize(null|string|int|bool $value): mixed
    {
        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        return $this
            ->clock
            ->at($value, new Format)
            ->match(
                static fn($point) => $point,
                static fn() => throw new \LogicException("'$value' is not a date"),
            );
    }
}
