<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository\Normalize;

use Formal\ORM\{
    Definition\Aggregate\Collection as Definition,
    Raw\Aggregate\Collection as Raw,
    Raw\Aggregate\Property,
    Raw\Aggregate\Id,
    Repository\KnownCollectionEntity,
};
use Innmind\Reflection\Extract;
use Innmind\Immutable\{
    Set,
    Map,
};

/**
 * @internal
 * @template T of object
 */
final class Collection
{
    /** @var Definition<T> */
    private Definition $definition;
    private Extract $extract;
    private KnownCollectionEntity $knowCollectionEntity;
    /** @var Set<non-empty-string> */
    private Set $properties;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(
        Definition $definition,
        Extract $extract,
        KnownCollectionEntity $knownCollectionEntity,
    ) {
        $this->definition = $definition;
        $this->extract = $extract;
        $this->knowCollectionEntity = $knownCollectionEntity;
        $this->properties = $definition
            ->properties()
            ->map(static fn($property) => $property->name());
    }

    /**
     * @param Set<T> $collection
     */
    public function __invoke(Id $id, Set $collection): Raw
    {
        $class = $this->definition->class();
        $entities = Map::of(
            ...$collection
                ->map(fn($object) => [
                    $this->knowCollectionEntity->reference(
                        $id,
                        $this->definition->name(),
                        $object,
                    ),
                    $object,
                ])
                ->toList(),
        );
        $entities = $entities->map(
            fn($_, $object) => ($this->extract)($object, $this->properties)->match(
                static fn($entity) => $entity,
                static fn() => throw new \LogicException("Failed to extract properties from '$class'"),
            ),
        );

        return Raw::of(
            $this->definition->name(),
            $entities
                ->map(
                    fn($_, $entity) => $this
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
                ->values()
                ->map(Raw\Entity::of(...)) // TODO inject the reference
                ->toSet(),
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
        KnownCollectionEntity $knownCollectionEntity,
    ): self {
        return new self($definition, $extract, $knownCollectionEntity);
    }
}
