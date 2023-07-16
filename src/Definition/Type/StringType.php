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
    Primitive,
};
use Innmind\Immutable\Maybe;

/**
 * @implements Type<string>
 */
final class StringType implements Type
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
                static fn($type) => $type->accepts(Nullable::of(Primitive::string())) ||
                    $type->accepts(Primitive::string()),
            )
            ->map(static fn() => new self);
    }

    public function normalize(mixed $value): null|string|int|bool
    {
        return $value;
    }

    public function denormalize(null|string|int|bool $value): mixed
    {
        if (\is_null($value)) {
            return null;
        }

        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        return $value;
    }
}
