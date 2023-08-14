<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\{
    Definition\Type,
    Definition\Types,
    Id,
};
use Innmind\Type\{
    Type as Concrete,
    Nullable,
    ClassName,
};
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 * @implements Type<Id>
 */
final class IdType implements Type
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
            ->filter(static fn($type) => $type->accepts(ClassName::of(Id::class)))
            ->map(static fn() => new self);
    }

    public function normalize(mixed $value): null|string|int|bool
    {
        return $value->toString();
    }

    public function denormalize(null|string|int|bool $value): mixed
    {
        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        /**
         * Using a fake class here but it doesn't matter as it's only used to
         * type the id in userland code, the concrete value is not stored.
         * And this avoids the necessity to specify the class as an attribute on
         * the property.
         * @psalm-suppress ArgumentTypeCoercion
         */
        return Id::of('stdClass', $value);
    }
}
