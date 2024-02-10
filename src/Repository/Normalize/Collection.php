<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository\Normalize;

use Formal\ORM\{
    Definition\Aggregate\Collection as Definition,
    Raw\Aggregate\Collection as Raw,
    Raw\Aggregate\Property,
};
use Innmind\Reflection\Extract;
use Innmind\Immutable\Set;

/**
 * @internal
 * @template T of object
 */
final class Collection
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
     * @param Set<T> $collection
     */
    public function __invoke(Set $collection): Raw
    {
        $class = $this->definition->class();
        $entities = $collection->map(
            fn($object) => ($this->extract)($object, $this->properties)->match(
                static fn($entities) => $entities,
                static fn() => throw new \LogicException("Failed to extract properties from '$class'"),
            ),
        );

        return Raw::of(
            $this->definition->name(),
            $entities
                ->map(
                    fn($entity) => $this
                        ->definition
                        ->properties()
                        ->flatMap(
                            static fn($property) => $entity
                                ->get($property->name())
                                ->map(static fn($value) => Property::of(
                                    $property->name(),
                                    $property->type()->normalize($value),
                                ))
                                ->toSequence()
                                ->toSet(),
                        ),
                )
                ->map(Raw\Entity::of(...)),
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
