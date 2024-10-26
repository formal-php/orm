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
    private function __construct(
        Definition $definition,
        Extract $extract,
    ) {
        $this->definition = $definition;
        $this->extract = $extract;
        $this->properties = $definition
            ->properties()
            ->map(static fn($property) => $property->name())
            ->toSet();
    }

    /**
     * @param Set<T> $collection
     */
    public function __invoke(Set $collection): Raw
    {
        $definition = $this->definition;
        $class = $this->definition->class();
        $properties = $this->properties;
        $extract = $this->extract;
        $entities = $collection->map(
            static fn($object) => $extract($object, $properties)->match(
                static fn($entity) => $entity,
                static fn() => throw new \LogicException("Failed to extract properties from '$class'"),
            ),
        );

        return Raw::of(
            $this->definition->name(),
            $entities
                ->map(
                    static fn($entity) => $definition
                        ->properties()
                        ->flatMap(
                            static fn($property) => $entity
                                ->get($property->name())
                                ->map(static fn($value) => Property::of(
                                    $property->name(),
                                    $property->type()->normalize($value),
                                ))
                                ->toSequence(),
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
    public static function of(
        Definition $definition,
        Extract $extract,
    ): self {
        return new self($definition, $extract);
    }
}
