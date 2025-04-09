<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Effect,
    Raw\Aggregate,
    Raw\Diff,
};
use Innmind\Immutable\Sequence;

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
    public function __invoke(Effect\Normalized\Properties|Effect\Normalized\Entity|Effect\Normalized\Child\Add $effect): callable
    {
        /** @var Sequence<Aggregate\Property> */
        $properties = Sequence::of();
        /** @var Sequence<Aggregate\Entity> */
        $entities = Sequence::of();
        /** @var Sequence<Aggregate\Optional> */
        $optionals = Sequence::of();
        /** @var Sequence<Aggregate\Collection> */
        $collections = Sequence::of();

        if ($effect instanceof Effect\Normalized\Entity) {
            $entities = Sequence::of(Aggregate\Entity::of(
                $effect->property(),
                $effect
                    ->effects()
                    ->map(static fn($effect) => Aggregate\Property::of(
                        $effect->property(),
                        $effect->value(),
                    )),
            ));
        } else if ($effect instanceof Effect\Normalized\Child\Add) {
            $collections = Sequence::of(Aggregate\Collection::of(
                $effect->property(),
                $effect->entities()->toSet(),
            ));
        } else {
            /** @psalm-suppress MixedArgument */
            $properties = $effect->effects()->map(
                static fn($effect) => Aggregate\Property::of(
                    $effect->property(),
                    $effect->value(),
                ),
            );
        }

        return static fn(Aggregate $aggregate) => Diff::of(
            $aggregate->id(),
            $properties,
            $entities,
            $optionals,
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
