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
            ->map(static fn($property) => $property->name())
            ->toSet();
    }

    /**
     * @param Maybe<T> $optional
     */
    public function __invoke(Maybe $optional): Raw
    {
        $definition = $this->definition;
        $class = $this->definition->class();
        $properties = $this->properties;
        $extract = $this->extract;
        $properties = $optional->map(
            static fn($object) => $extract($object, $properties)->match(
                static fn($properties) => $properties,
                static fn() => throw new \LogicException("Failed to extract properties from '$class'"),
            ),
        );

        return Raw::of(
            $this->definition->name(),
            $properties->map(
                static fn($properties) => $definition
                    ->properties()
                    ->flatMap(
                        static fn($property) => $properties
                            ->get($property->name())
                            ->map(static fn($value) => Property::of(
                                $property->name(),
                                $property->type()->normalize($value),
                            ))
                            ->toSequence(),
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
