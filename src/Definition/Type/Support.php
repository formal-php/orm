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
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class Support
{
    /** @var class-string */
    private string $class;
    private Type $via;

    /**
     * @param class-string $class
     */
    private function __construct(string $class, Type $via)
    {
        $this->class = $class;
        $this->via = $via;
    }

    /**
     * @return Maybe<Type>
     */
    public function __invoke(Types $types, Concrete $type): Maybe
    {
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
