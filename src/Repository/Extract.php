<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
    Id,
};
use Innmind\Reflection;
use Innmind\Immutable\{
    Set,
    Map,
};

/**
 * @template T of object
 */
final class Extract
{
    /** @var Definition<T> */
    private Definition $definition;
    private Reflection\Extract $extract;
    /** @var Set<non-empty-string> */
    private Set $allProperties;
    /** @var \Closure(T): Id<T> */
    private \Closure $extractId;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->extract = new Reflection\Extract;
        $this->allProperties = $definition
            ->properties()
            ->map(static fn($property) => $property->name())
            ->merge(
                $definition
                    ->entities()
                    ->map(static fn($entity) => $entity->name()),
            )
            ->merge(
                $definition
                    ->optionals()
                    ->map(static fn($optional) => $optional->name()),
            )
            ->merge(
                $definition
                    ->collections()
                    ->map(static fn($collection) => $collection->name()),
            );
        /**
         * @psalm-suppress InvalidArgument
         * @var \Closure(T): Id<T>
         */
        $this->extractId = static fn(object $aggregate): Id => $definition->id()->extract($aggregate);
    }

    /**
     * @param T $aggregate
     *
     * @return Denormalized<T>
     */
    public function __invoke(object $aggregate): Denormalized
    {
        $class = $this->definition->class();

        return Denormalized::of(
            ($this->extractId)($aggregate),
            ($this->extract)($aggregate, $this->allProperties)->match(
                static fn($properties) => $properties,
                static fn() => throw new \LogicException("Failed to extract properties from '$class'"),
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
