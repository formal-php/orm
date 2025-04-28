<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Effect,
    Raw\Aggregate,
    Raw\Diff,
    Specification,
};
use Innmind\Specification\Sign;
use Innmind\Immutable\{
    Sequence,
    Maybe,
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
        /** @psalm-suppress MixedArgumentTypeCoercion */
        [$properties, $entities, $optionals, $collections] = $effect->match(
            static fn($properties) => [
                $properties->map(static fn($effect) => Aggregate\Property::of(
                    $effect->property(),
                    $effect->value(),
                )),
                null,
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
                null,
            ],
            static fn($optional, $properties) => [
                null,
                null,
                Sequence::of(Aggregate\Optional::of(
                    $optional,
                    Maybe::just(
                        $properties->map(static fn($effect) => Aggregate\Property::of(
                            $effect->property(),
                            $effect->value(),
                        )),
                    ),
                )),
                null,
            ],
            static fn($optional) => [
                null,
                null,
                Sequence::of(Aggregate\Optional::of(
                    $optional,
                    Maybe::nothing(),
                )),
                null,
            ],
            static fn($collection, $entities) => [
                null,
                null,
                null,
                static fn(Sequence $collections) => self::addChild(
                    $collection,
                    $entities,
                    $collections,
                ),
            ],
            static fn($collection, $comparator) => [
                null,
                null,
                null,
                static fn(Sequence $collections) => self::removeChild(
                    $collection,
                    $comparator,
                    $collections,
                ),
            ],
        );
        $properties ??= Sequence::of();
        $entities ??= Sequence::of();
        $optionals ??= Sequence::of();

        return static fn(Aggregate $aggregate) => Diff::of(
            $aggregate->id(),
            $properties,
            $entities,
            $optionals,
            match ($collections) {
                null => $aggregate->collections(),
                default => $collections($aggregate->collections()),
            },
        );
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self;
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Aggregate\Collection\Entity> $entities
     * @param Sequence<Aggregate\Collection> $collections
     *
     * @return Sequence<Aggregate\Collection>
     */
    private static function addChild(
        string $collection,
        Sequence $entities,
        Sequence $collections,
    ): Sequence {
        return $collections->map(
            static fn($existing) => match ($existing->name()) {
                $collection => Aggregate\Collection::of(
                    $collection,
                    $existing->entities()->merge($entities->toSet()),
                ),
                default => $existing,
            },
        );
    }

    /**
     * @psalm-pure
     *
     * @param Sequence<Aggregate\Collection> $collections
     *
     * @return Sequence<Aggregate\Collection>
     */
    private static function removeChild(
        string $collection,
        Specification\Property $comparator,
        Sequence $collections,
    ): Sequence {
        return $collections->map(
            static fn($existing) => match ($existing->name()) {
                $collection => Aggregate\Collection::of(
                    $collection,
                    $existing->entities()->exclude(
                        static fn($entity) => $entity
                            ->properties()
                            ->find(static fn($property) => $property->name() === $comparator->property())
                            ->match(
                                static fn($property) => self::matches($property, $comparator),
                                static fn() => false,
                            ),
                    ),
                ),
                default => $existing,
            },
        );
    }

    /**
     * @psalm-pure
     */
    private static function matches(
        Aggregate\Property $property,
        Specification\Property $specification,
    ): bool {
        /**
         * @psalm-suppress InvalidArgument
         * @psalm-suppress PossiblyInvalidArgument
         */
        return match ($specification->sign()) {
            Sign::equality => $property->value() === $specification->value(),
            Sign::lessThan => $property->value() < $specification->value(),
            Sign::moreThan => $property->value() > $specification->value(),
            Sign::startsWith => \is_string($property->value()) && \str_starts_with($property->value(), $specification->value()),
            Sign::endsWith => \is_string($property->value()) && \str_ends_with($property->value(), $specification->value()),
            Sign::contains => \is_string($property->value()) && \str_contains($property->value(), $specification->value()),
            Sign::in => \in_array($property->value(), $specification->value(), true),
        };
    }
}
