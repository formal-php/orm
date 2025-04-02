<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Type,
    Types,
};
use Innmind\Type\{
    Type as Concrete,
    Primitive,
};
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 * @implements Type<int>
 */
enum IntType implements Type
{
    case instance;

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function of(Types $types, Concrete $type): Maybe
    {
        return Maybe::just($type)
            ->filter(static fn($type) => $type->accepts(Primitive::int()))
            ->map(static fn() => self::instance);
    }

    public function normalize(mixed $value): null|string|int|float|bool
    {
        return $value;
    }

    public function denormalize(null|string|int|float|bool $value): mixed
    {
        if (!\is_int($value)) {
            throw new \LogicException("'$value' is not an integer");
        }

        return $value;
    }
}
