<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository\Denormalize;

use Formal\ORM\{
    Definition\Aggregate\Collection as Definition,
    Definition\Aggregate\Property,
    Raw\Aggregate\Collection as Raw,
    Repository\KnownCollectionEntity,
    Id,
};
use Innmind\Reflection\Instanciate;
use Innmind\Immutable\{
    Map,
    Set,
};

/**
 * @internal
 * @template T of object
 */
final class Collection
{
    /** @var Definition<T> */
    private Definition $definition;
    private Instanciate $instanciate;
    private KnownCollectionEntity $knownCollectionEntity;
    /** @var Map<non-empty-string, Property<T, mixed>> */
    private Map $properties;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(
        Definition $definition,
        Instanciate $instanciate,
        KnownCollectionEntity $knownCollectionEntity,
    ) {
        $this->definition = $definition;
        $this->instanciate = $instanciate;
        $this->knownCollectionEntity = $knownCollectionEntity;
        $this->properties = Map::of(
            ...$definition
                ->properties()
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );
    }

    /**
     * @return Set<T>
     */
    public function __invoke(Id $id, Raw $collection): Set
    {
        $class = $this->definition->class();
        $name = $collection->name();
        // We use a weak reference to the aggregate id otherwise the closure below
        // will always keep a reference to the id and all the weak maps that depend
        // on this id will keep the associated data.
        // This means that when reading lots of data the memory will always increase.
        $idReference = \WeakReference::create($id);

        return $collection
            ->newEntities()
            ->map(function($entity) use ($class, $idReference, $name) {
                $reference = $entity->reference();

                $entity = Map::of(
                    ...$entity
                        ->properties()
                        ->flatMap(
                            fn($property) => $this
                                ->properties
                                ->get($property->name())
                                ->map(static fn($definition): mixed => $definition->type()->denormalize($property->value()))
                                ->map(static fn($value) => [$property->name(), $value])
                                ->toSequence(),
                        )
                        ->toList(),
                );

                /** @var T */
                return ($this->instanciate)($class, $entity)
                    ->map(fn($object) => $this->knownCollectionEntity->add(
                        $idReference,
                        $name,
                        $object,
                        $reference,
                    ))
                    ->match(
                        static fn($object) => $object,
                        static fn() => throw new \RuntimeException("Unable to denormalize collection of type '$class'"),
                    );
            });
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
        Instanciate $instanciate,
        KnownCollectionEntity $knownCollectionEntity,
    ): self {
        return new self($definition, $instanciate, $knownCollectionEntity);
    }
}
