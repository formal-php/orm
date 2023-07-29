<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\Raw\{
    Aggregate,
    Diff,
};
use Innmind\Immutable\Set;

final class Apply
{
    private Diff $diff;

    private function __construct(Diff $diff)
    {
        $this->diff = $diff;
    }

    public function __invoke(Aggregate $source): Aggregate
    {
        return Aggregate::of(
            $this->diff->id(),
            $this->applyProperties($source->properties(), $this->diff->properties()),
            $this->applyEntities($source->entities(), $this->diff->entities()),
            $this->applyOptionals($source->optionals(), $this->diff->optionals()),
            $this->applyCollections($source->collections(), $this->diff->collections()),
        );
    }

    public static function of(Diff $diff): self
    {
        return new self($diff);
    }

    /**
     * @param Set<Aggregate\Property> $then
     * @param Set<Aggregate\Property> $now
     *
     * @return Set<Aggregate\Property>
     */
    private function applyProperties(Set $then, Set $now): Set
    {
        return $then->map(
            static fn($property) => $now
                ->find($property->referenceSame(...))
                ->match(
                    static fn($diff) => $diff,
                    static fn() => $property,
                ),
        );
    }

    /**
     * @param Set<Aggregate\Entity> $then
     * @param Set<Aggregate\Entity> $now
     *
     * @return Set<Aggregate\Entity>
     */
    private function applyEntities(Set $then, Set $now): Set
    {
        return $then->map(
            fn($entity) => $now
                ->find($entity->referenceSame(...))
                ->match(
                    fn($diff) => Aggregate\Entity::of(
                        $diff->name(),
                        $this->applyProperties($entity->properties(), $diff->properties()),
                    ),
                    static fn() => $entity,
                ),
        );
    }

    /**
     * @param Set<Aggregate\Optional> $then
     * @param Set<Aggregate\Optional> $now
     *
     * @return Set<Aggregate\Optional>
     */
    private function applyOptionals(Set $then, Set $now): Set
    {
        return $then->map(
            fn($optional) => $now
                ->find($optional->referenceSame(...))
                ->match(
                    fn($diff) => Aggregate\Optional::of(
                        $diff->name(),
                        $diff
                            ->properties()
                            ->map(
                                fn($properties) => $optional
                                    ->properties()
                                    ->match(
                                        fn($then) => $this->applyProperties($then, $properties),
                                        static fn() => $properties,
                                    ),
                            ),
                    ),
                    static fn() => $optional,
                ),
        );
    }

    /**
     * @param Set<Aggregate\Collection> $then
     * @param Set<Aggregate\Collection> $now
     *
     * @return Set<Aggregate\Collection>
     */
    private function applyCollections(Set $then, Set $now): Set
    {
        return $then->map(
            static fn($collection) => $now
                ->find($collection->referenceSame(...))
                ->match(
                    static fn($collection) => $collection,
                    static fn() => $collection,
                ),
        );
    }
}
