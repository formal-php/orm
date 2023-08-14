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
};
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

/**
 * @psalm-immutable
 * @template I
 * @implements Type<?I>
 */
final class NullableType implements Type
{
    /** @var Type<I> */
    private Type $inner;

    /**
     * @param Type<I> $inner
     */
    private function __construct(Type $inner)
    {
        $this->inner = $inner;
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function of(Types $types, Concrete $type): Maybe
    {
        return Maybe::just($type)
            ->keep(Instance::of(Nullable::class))
            ->flatMap(static fn($type) => $types($type->type()))
            ->map(static fn($inner) => new self($inner));
    }

    /**
     * @return Type<I>
     */
    public function inner(): Type
    {
        return $this->inner;
    }

    public function normalize(mixed $value): null|string|int|bool
    {
        if (\is_null($value)) {
            return null;
        }

        return $this->inner->normalize($value);
    }

    public function denormalize(null|string|int|bool $value): mixed
    {
        if (\is_null($value)) {
            return null;
        }

        return $this->inner->denormalize($value);
    }
}
