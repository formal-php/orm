<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Type,
    Types,
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
     * @param non-empty-string $type
     *
     * @return Maybe<self>
     */
    public static function of(Types $types, string $type): Maybe
    {
        return Maybe::just($type)
            ->filter(static fn($type) => match ($type) {
                Str::class, '?'.Str::class => true,
                default => false,
            })
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
