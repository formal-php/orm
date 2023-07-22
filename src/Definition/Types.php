<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Innmind\Type\Type as Concrete;
use Innmind\Immutable\Maybe;

final class Types
{
    /** @var list<callable(self, Concrete, ?Template): Maybe<Type>> */
    private array $builders;

    /**
     * @no-named-arguments
     *
     * @param callable(self, Concrete, ?Template): Maybe<Type> $builders
     */
    private function __construct(callable ...$builders)
    {
        $this->builders = $builders;
    }

    /**
     * @return Maybe<Type>
     */
    public function __invoke(
        Concrete $type,
        Template $template = null,
    ): Maybe {
        /** @var Maybe<Type> */
        $found = Maybe::nothing();

        foreach ($this->builders as $build) {
            $found = $found->otherwise(fn() => $build($this, $type, $template));
        }

        return $found;
    }

    /**
     * @no-named-arguments
     *
     * @param callable(self, Concrete, ?Template): Maybe<Type> $builders
     */
    public static function of(callable ...$builders): self
    {
        return new self(
            Type\MaybeType::of(...),
            Type\StringType::of(...),
            Type\StrType::of(...),
            Type\IntType::of(...),
            Type\BoolType::of(...),
            ...$builders,
        );
    }

    public static function default(): self
    {
        return new self(
            Type\MaybeType::of(...),
            Type\StringType::of(...),
            Type\StrType::of(...),
            Type\IntType::of(...),
            Type\BoolType::of(...),
        );
    }
}
