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
final class IntType implements Type
{
    private function __construct()
    {
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function of(Types $types, Concrete $type): Maybe
    {
        return Maybe::just($type)
            ->filter(static fn($type) => $type->accepts(Primitive::int()))
            ->map(static fn() => new self);
    }

    public function normalize(mixed $value): null|string|int|bool
    {
        return $value;
    }

    public function denormalize(null|string|int|bool $value): mixed
    {
        if (!\is_int($value)) {
            throw new \LogicException("'$value' is not an integer");
        }

        return $value;
    }
}
