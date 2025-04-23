<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Effect,
    Raw\Aggregate,
    Raw\Diff,
};
use Innmind\Immutable\{
    Sequence,
    Predicate\Instance,
};

/**
 * @internal
 */
final class EncodeEffect
{
    private function __construct()
    {
    }

    /**
     * @return callable(Aggregate): Diff
     */
    public function __invoke(Effect\Normalized $effect): callable
    {
        [$properties, $entities, $collections] = $effect->match(
            static fn($properties) => [
                $properties->map(static fn($effect) => Aggregate\Property::of(
                    $effect->property(),
                    $effect->value(),
                )),
                null,
                null,
            ],
            static fn($entity, $properties) => [
                null,
                Sequence::of(Aggregate\Entity::of(
                    $entity,
                    $properties->map(static fn($effect) => Aggregate\Property::of(
                        $effect->property(),
                        $effect->value(),
                    )),
                )),
                null,
            ],
            static fn($collection, $entities) => [
                null,
                null,
                Sequence::of(Aggregate\Collection::of(
                    $collection,
                    $entities->toSet(),
                )),
            ],
        );
        $properties ??= Sequence::of();
        $entities ??= Sequence::of();
        $collections ??= Sequence::of();
        // to please Psalm
        $collections = $collections->keep(Instance::of(Aggregate\Collection::class));

        return static fn(Aggregate $aggregate) => Diff::of(
            $aggregate->id(),
            $properties,
            $entities,
            Sequence::of(),
            $aggregate->collections()->map(
                static fn($collection) => $collections
                    ->find(static fn($toModify) => $toModify->name() === $collection->name())
                    ->map(static fn($toModify) => $collection->entities()->merge(
                        $toModify->entities(),
                    ))
                    ->match(
                        static fn($entities) => Aggregate\Collection::of(
                            $collection->name(),
                            $entities,
                        ),
                        static fn() => $collection,
                    ),
            ),
        );
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self;
    }
}
