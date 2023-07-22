<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Type,
    Types,
    Template,
};
use Innmind\Type\{
    Type as Concrete,
    ClassName,
};
use Innmind\Immutable\Maybe;

/**
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
     * @return Maybe<self>
     */
    public static function of(Types $types, Concrete $type, Template $template = null): Maybe
    {
        return Maybe::just($type)
            ->filter(static fn($type) => $type->accepts(ClassName::of(Maybe::class)))
            ->flatMap(static fn() => Maybe::of($template))
            ->flatMap(static fn($template) => $types($template->type()))
            ->map(static fn($inner) => new self($inner));
    }

    public function normalize(mixed $value): null|string|int|bool
    {
        if (\is_null($value)) {
            return null;
        }

        return $value->match(
            $this->inner->normalize(...),
            static fn() => null,
        );
    }

    public function denormalize(null|string|int|bool $value): mixed
    {
        if (\is_null($value)) {
            /** @var Maybe<I> */
            return Maybe::nothing();
        }

        return Maybe::just($value)->map($this->inner->denormalize(...));
    }
}
