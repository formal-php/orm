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
 * @implements Type<string>
 */
enum StringType implements Type
{
    case instance;

    /**
     * @psalm-pure
     */
    public static function new(): self
    {
        return self::instance;
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function of(Types $types, Concrete $type): Maybe
    {
        return Maybe::just($type)
            ->filter(static fn($type) => $type->accepts(Primitive::string()))
            ->map(static fn() => self::instance);
    }

    #[\Override]
    public function normalize(mixed $value): null|string|int|float|bool
    {
        return $value;
    }

    #[\Override]
    public function denormalize(null|string|int|float|bool $value): mixed
    {
        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        return $value;
    }
}
