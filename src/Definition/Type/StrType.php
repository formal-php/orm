<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Type,
    Types,
};
use Innmind\Type\{
    Type as Concrete,
    Nullable,
    ClassName,
};
use Innmind\Immutable\{
    Maybe,
    Str,
};

/**
 * @implements Type<Str>
 */
final class StrType implements Type
{
    private function __construct()
    {
    }

    /**
     * @return Maybe<self>
     */
    public static function of(Types $types, Concrete $type): Maybe
    {
        return Maybe::just($type)
            ->filter(
                static fn($type) => $type->accepts(Nullable::of(ClassName::of(Str::class))) ||
                    $type->accepts(ClassName::of(Str::class)),
            )
            ->map(static fn() => new self);
    }

    public function normalize(mixed $value): null|string|int|bool
    {
        if (\is_null($value)) {
            return null;
        }

        return $value->toString();
    }

    public function denormalize(null|string|int|bool $value): mixed
    {
        if (\is_null($value)) {
            return null;
        }

        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        return Str::of($value);
    }
}
