<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\Type;
use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 * @implements Type<Str>
 */
final class StrType implements Type
{
    private function __construct()
    {
    }

    /**
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self;
    }

    public function normalize(mixed $value): null|string|int|float|bool
    {
        return $value->toString();
    }

    public function denormalize(null|string|int|float|bool $value): mixed
    {
        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        return Str::of($value);
    }
}
