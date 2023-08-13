<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Sort as Direction,
    Sort\Entity,
    Sort\Property,
};
use Innmind\Immutable\{
    Maybe,
    Str,
};

/**
 * @psalm-immutable
 * @template T of object
 */
final class Sort
{
    /** @var Definition<T> */
    private Definition $definition;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @param non-empty-string $property
     */
    public function __invoke(string $property, Direction $direction): Entity|Property
    {
        return $this
            ->definition
            ->properties()
            ->find(static fn($known) => $known->name() === $property)
            ->map(static fn() => Property::of($property, $direction))
            ->otherwise(
                function() use ($property, $direction) {
                    $parts = Str::of($property)
                        ->split('.')
                        ->map(static fn($part) => $part->toString());

                    return Maybe::all($parts->first(), $parts->last())->flatMap(
                        fn(string $entity, string $name) => $this
                            ->definition
                            ->entities()
                            ->find(static fn($known) => $known->name() === $entity)
                            ->flatMap(
                                static fn($entity) => $entity
                                    ->properties()
                                    ->find(static fn($property) => $property->name() === $name)
                                    ->map(static fn($property) => Entity::of(
                                        $entity->name(),
                                        Property::of($property->name(), $direction),
                                    )),
                            ),
                    );
                },
            )
            ->match(
                static fn($sort) => $sort,
                static fn() => throw new \LogicException("Unknown property '$property'"),
            );
    }

    /**
     * @psalm-pure
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
