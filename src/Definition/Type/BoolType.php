<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Type,
    Types,
};
use Innmind\Immutable\Maybe;

/**
 * @implements Type<bool>
 */
final class BoolType implements Type
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
                'bool', '?bool' => true,
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
        return match ($value) {
            null => null,
            0 => false,
            1 => true,
            true => true,
            false => false,
            default => throw new \LogicException("'$value' is not a boolean"),
        };
    }
}
