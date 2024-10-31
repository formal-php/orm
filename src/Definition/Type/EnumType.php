<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Type,
    Types,
};
use Innmind\Type\{
    Type as Concrete,
    ClassName,
};
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

/**
 * @psalm-immutable
 * @template T of \UnitEnum
 * @implements Type<T>
 */
final class EnumType implements Type
{
    /** @var class-string<T> */
    private string $class;

    /**
     * @param class-string<T> $class
     */
    private function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function of(Types $types, Concrete $type): Maybe
    {
        /**
         * @psalm-suppress InvalidTemplateParam
         * @psalm-suppress ArgumentTypeCoercion
         */
        return Maybe::just($type)
            ->keep(Instance::of(ClassName::class))
            ->filter(static fn($type) => $type->enum())
            ->map(static fn($type) => new self($type->toString()));
    }

    public function normalize(mixed $value): null|string|int|float|bool
    {
        return $value->name;
    }

    public function denormalize(null|string|int|float|bool $value): mixed
    {
        foreach ($this->class::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        throw new \LogicException("'$value' is not a case of the enum '{$this->class}'");
    }
}
