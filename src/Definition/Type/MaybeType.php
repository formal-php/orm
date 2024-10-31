<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Type,
    Types,
    Contains,
};
use Innmind\Type\{
    Type as Concrete,
    ClassName,
};
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 * @template I
 * @implements Type<Maybe<I>>
 */
final class MaybeType implements Type
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
    public static function of(Types $types, Concrete $type, Contains $contains = null): Maybe
    {
        return Maybe::just($type)
            ->filter(static fn($type) => $type->accepts(ClassName::of(Maybe::class)))
            ->flatMap(static fn() => Maybe::of($contains))
            ->flatMap(static fn($contains) => $types($contains->type()))
            ->map(static fn($inner) => new self($inner));
    }

    /**
     * @return Type<I>
     */
    public function inner(): Type
    {
        return $this->inner;
    }

    public function normalize(mixed $value): null|string|int|float|bool
    {
        return $value->match(
            $this->inner->normalize(...),
            static fn() => null,
        );
    }

    public function denormalize(null|string|int|float|bool $value): mixed
    {
        return Maybe::of($value)->map($this->inner->denormalize(...));
    }
}
