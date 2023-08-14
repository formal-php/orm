<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository\Normalize;

use Formal\ORM\{
    Definition\Aggregate\Optional as Definition,
    Raw\Aggregate\Optional as Raw,
    Raw\Aggregate\Property,
};
use Innmind\Reflection\Extract;
use Innmind\Immutable\{
    Set,
    Maybe,
};

/**
 * @internal
 * @template T of object
 */
final class Optional
{
    /** @var Definition<T> */
    private Definition $definition;
    private Extract $extract;
    /** @var Set<non-empty-string> */
    private Set $properties;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition, Extract $extract)
    {
        $this->definition = $definition;
        $this->extract = $extract;
        $this->properties = $definition
            ->properties()
            ->map(static fn($property) => $property->name());
    }

    /**
     * @param Maybe<T> $optional
     */
    public function __invoke(Maybe $optional): Raw
    {
        $class = $this->definition->class();
        $properties = $optional->map(
            fn($object) => ($this->extract)($object, $this->properties)->match(
                static fn($properties) => $properties,
                static fn() => throw new \LogicException("Failed to extract properties from '$class'"),
            ),
        );

        return Raw::of(
            $this->definition->name(),
            $properties->map(
                fn($properties) => $this
                    ->definition
                    ->properties()
                    ->flatMap(
                        static fn($property) => $properties
                            ->get($property->name())
                            ->map(static fn($value) => Property::of(
                                $property->name(),
                                $property->type()->normalize($value),
                            ))
                            ->toSequence()
                            ->toSet(),
                    ),
            ),
        );
    }

    /**
     * @internal
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(Definition $definition, Extract $extract): self
    {
        return new self($definition, $extract);
    }
}
