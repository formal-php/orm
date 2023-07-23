<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
};
use Innmind\Reflection\Extract;
use Innmind\Immutable\{
    Set,
    Map,
};

/**
 * @template T of object
 */
final class Normalize
{
    /** @var Definition<T> */
    private Definition $definition;
    private Extract $extract;
    /** @var Set<non-empty-string> */
    private Set $allProperties;
    /** @var Map<Definition\Entity, Normalize\Entity> */
    private Map $normalizeEntity;
    /** @var \Closure(T): Aggregate\Id */
    private \Closure $extractId;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->extract = new Extract;
        $this->allProperties = $definition
            ->properties()
            ->map(static fn($property) => $property->name())
            ->merge(
                $definition
                    ->entities()
                    ->map(static fn($entity) => $entity->property()),
            );
        $this->normalizeEntity = Map::of(
            ...$definition
                ->entities()
                ->map(fn($entity) => [$entity, Normalize\Entity::of(
                    $entity,
                    $this->extract,
                )])
                ->toList(),
        );
        $id = $definition->id();
        /**
         * @psalm-suppress InvalidArgument
         * @var \Closure(T): Aggregate\Id
         */
        $this->extractId = static fn(object $aggregate): Aggregate\Id => $id->normalize($id->extract($aggregate));
    }

    /**
     * @param T $aggregate
     */
    public function __invoke(object $aggregate): Aggregate
    {
        $id = ($this->extractId)($aggregate);
        $class = $this->definition->class();
        $properties = ($this->extract)($aggregate, $this->allProperties)->match(
            static fn($properties) => $properties,
            static fn() => throw new \LogicException("Failed to extract properties from '$class'"),
        );

        return Aggregate::of(
            ($this->extractId)($aggregate),
            $this
                ->definition
                ->properties()
                ->flatMap(
                    static fn($property) => $properties
                        ->get($property->name())
                        ->map(static fn($value) => Aggregate\Property::of(
                            $property->name(),
                            $property->type()->normalize($value),
                        ))
                        ->toSequence()
                        ->toSet(),
                ),
            $this
                ->definition
                ->entities()
                ->flatMap(
                    fn($entity) => $this
                        ->normalizeEntity
                        ->get($entity)
                        ->flatMap(
                            static fn($normalize) => $properties
                                ->get($entity->property())
                                ->map($normalize),
                        )
                        ->toSequence()
                        ->toSet(),
                ),
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
}
