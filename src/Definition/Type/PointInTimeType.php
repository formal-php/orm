<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Type,
    Type\PointInTimeType\Formats,
};
use Innmind\Time\{
    Clock,
    Point,
};

/**
 * @psalm-immutable
 * @implements Type<Point>
 */
final class PointInTimeType implements Type
{
    private function __construct(
        private Clock $clock,
        private Formats $format,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function new(Clock $clock): self
    {
        return new self($clock, Formats::default);
    }

    #[\Override]
    public function normalize(mixed $value): null|string|int|float|bool
    {
        return $value->format($this->format);
    }

    #[\Override]
    public function denormalize(null|string|int|float|bool $value): mixed
    {
        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        if ($value === '') {
            throw new \LogicException('Date cannot be empty');
        }

        return $this
            ->clock
            ->at($value, $this->format)
            ->unwrap();
    }
}
