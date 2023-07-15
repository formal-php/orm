<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Type,
    Types,
};
use Innmind\Immutable\Maybe;

/**
 * @implements Type<int>
 */
final class IntType implements Type
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
                'int', '?int' => true,
                default => false,
            })
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

        if (!\is_int($value)) {
            throw new \LogicException("'$value' is not an integer");
        }

        return $value;
    }
}
