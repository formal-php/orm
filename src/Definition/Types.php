<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Innmind\Immutable\Maybe;

final class Types
{
    /** @var list<callable(self, non-empty-string): Maybe<Type>> */
    private array $builders;

    /**
     * @no-named-arguments
     *
     * @param callable(self, non-empty-string): Maybe<Type> $builders
     */
    private function __construct(callable ...$builders)
    {
        $this->builders = $builders;
    }

    /**
     * @param non-empty-string $type
     *
     * @return Maybe<Type>
     */
    public function __invoke(string $type): Maybe
    {
        /** @var Maybe<Type> */
        $found = Maybe::nothing();

        foreach ($this->builders as $build) {
            $found = $found->otherwise(fn() => $build($this, $type));
        }

        return $found;
    }

    public static function default(): self
    {
        return new self(
            Type\StringType::of(...),
            Type\IntType::of(...),
            Type\BoolType::of(...),
        );
    }
}
