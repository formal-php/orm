<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository\Denormalize;

use Formal\ORM\{
    Definition\Aggregate\Entity as Definition,
    Definition\Aggregate\Property,
    Raw\Aggregate\Entity as Raw,
};
use Innmind\Reflection\Instanciate;
use Innmind\Immutable\Map;

/**
 * @internal
 * @template T of object
 */
final class Entity
{
    /** @var Definition<T> */
    private Definition $definition;
    private Instanciate $instanciate;
    /** @var Map<non-empty-string, Property<T, mixed>> */
    private Map $properties;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition, Instanciate $instanciate)
    {
        $this->definition = $definition;
        $this->instanciate = $instanciate;
        $this->properties = Map::of(
            ...$definition
                ->properties()
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );
    }

    /**
     * @return T
     */
    public function __invoke(Raw $entity): object
    {
        $properties = Map::of(
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
        $class = $this->definition->class();

        /** @var T */
        return ($this->instanciate)($class, $properties)->match(
            static fn($entity) => $entity,
            static fn() => throw new \RuntimeException("Unable to denormalize entity of type '$class'"),
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
    public static function of(Definition $definition, Instanciate $instanciate): self
    {
        return new self($definition, $instanciate);
    }
}
