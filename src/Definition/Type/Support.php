<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type;

use Formal\ORM\Definition\{
    Contains,
    Type,
    Types,
};
use Innmind\Type\{
    Type as Concrete,
    ClassName,
};
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class Support
{
    /**
     * @param class-string $class
     */
    private function __construct(
        private string $class,
        private Type $via,
    ) {
    }

    /**
     * @return Maybe<Type>
     */
    public function __invoke(
        Types $types,
        Concrete $type,
        Contains|Contains\Primitive|null $contains = null,
    ): Maybe {
        return Maybe::just($type)
            ->filter(fn($type) => $type->accepts(ClassName::of($this->class)))
            ->map(fn() => $this->via);
    }

    /**
     * @psalm-pure
     *
     * @param class-string $class
     */
    public static function class(string $class, Type $via): self
    {
        return new self($class, $via);
    }
}
