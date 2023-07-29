<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Definition\Aggregate\Property,
    Definition\Aggregate\Entity,
    Raw\Aggregate,
    Id,
};
use Innmind\Reflection\Instanciate;
use Innmind\Immutable\{
    Map,
    Maybe,
};

/**
 * @template T of object
 */
final class Denormalize
{
    /** @var Definition<T> */
    private Definition $definition;
    private Instanciate $instanciate;
    /** @var \Closure(Aggregate\Id): Id<T> */
    private \Closure $denormalizeId;
    /** @var Map<non-empty-string, Property<T, mixed>> */
    private Map $properties;
    /** @var Map<non-empty-string, Denormalize\Entity> */
    private Map $entities;
    /** @var Map<non-empty-string, Denormalize\Optional> */
    private Map $optionals;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->instanciate = new Instanciate;
        /** @var \Closure(Aggregate\Id): Id<T> */
        $this->denormalizeId = $definition->id()->denormalize(...);
        $this->properties = Map::of(
            ...$definition
                ->properties()
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );
        $this->entities = Map::of(
            ...$definition
                ->entities()
                ->map(fn($entity) => [$entity->name(), Denormalize\Entity::of(
                    $entity,
                    $this->instanciate,
                )])
                ->toList(),
        );
        $this->optionals = Map::of(
            ...$definition
                ->optionals()
                ->map(fn($optional) => [$optional->name(), Denormalize\Optional::of(
                    $optional,
                    $this->instanciate,
                )])
                ->toList(),
        );
    }

    /**
     * @param ?Id<T> $id
     *
     * @return callable(Aggregate): T
     */
    public function __invoke(Id $id = null): callable
    {
        $id = match ($id) {
            null => $this->denormalizeId,
            default => static fn(Aggregate\Id $_) => $id,
        };
        $class = $this->definition->class();

        /**
         * @psalm-suppress InvalidReturnType
         * @psalm-suppress InvalidReturnStatement
         */
        return fn(Aggregate $data) => ($this->instanciate)(
            $class,
            $this->properties($data, $id($data->id())),
        )->match(
            static fn($aggregate) => $aggregate,
            static fn() => throw new \RuntimeException("Unable to denormalize aggregate of type '$class'"),
        );
    }

    /**
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(Definition $definition): self
    {
        return new self($definition);
    }

    /**
     * @param Id<T> $id
     * @return Map<non-empty-string, mixed>
     */
    private function properties(Aggregate $data, Id $id): Map
    {
        return Map::of(
            [$this->definition->id()->property(), $id],
            ...$data
                ->properties()
                ->flatMap(
                    fn($property) => $this
                        ->properties
                        ->get($property->name())
                        ->map(static fn($definition): mixed => $definition->type()->denormalize($property->value()))
                        ->map(static fn($value) => [$property->name(), $value])
                        ->toSequence()
                        ->toSet(),
                )
                ->toList(),
            ...$data
                ->entities()
                ->flatMap(
                    fn($entity) => $this
                        ->entities
                        ->get($entity->name())
                        ->map(static fn($denormalize): object => $denormalize($entity))
                        ->map(static fn($value) => [$entity->name(), $value])
                        ->toSequence()
                        ->toSet(),
                )
                ->toList(),
            ...$data
                ->optionals()
                ->flatMap(
                    fn($optional) => $this
                        ->optionals
                        ->get($optional->name())
                        ->map(static fn($denormalize): Maybe => $denormalize($optional))
                        ->map(static fn($value) => [$optional->name(), $value])
                        ->toSequence()
                        ->toSet(),
                )
                ->toList(),
        );
    }
}
