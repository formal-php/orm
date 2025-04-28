<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Effect\Child,
    Effect\Entity,
    Effect\Optional,
    Effect\Property,
    Effect\Properties,
    Effect\Normalized,
    Raw\Aggregate\Collection\Entity as RawEntity,
};
use Innmind\Specification\Comparator;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Effect
{
    private function __construct(
        private Properties|Entity|Optional|Optional\Nothing|Child\Add|Child\Remove $effect,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function property(string $property): Effect\Provider\Property
    {
        return Effect\Provider\Property::of(
            self::build(...),
            $property,
        );
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function entity(string $property): Effect\Provider\Entity
    {
        return Effect\Provider\Entity::of(
            self::build(...),
            $property,
        );
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function optional(string $property): Effect\Provider\Optional
    {
        return Effect\Provider\Optional::of(
            self::build(...),
            $property,
        );
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function child(string $property): Effect\Provider\Child
    {
        return Effect\Provider\Child::of(
            self::build(...),
            $property,
        );
    }

    /**
     * @internal
     *
     * @param callable(Property): Normalized\Property $property
     * @param callable(non-empty-string, Sequence<Property>): Sequence<Normalized\Property> $entity
     * @param callable(non-empty-string, Sequence<Property>): Sequence<Normalized\Property> $optional
     * @param callable(non-empty-string, Sequence<object>): Sequence<RawEntity> $addChild
     * @param callable(non-empty-string, Comparator): Specification\Property $removeChild
     */
    public function normalize(
        callable $property,
        callable $entity,
        callable $optional,
        callable $addChild,
        callable $removeChild,
    ): Normalized {
        if ($this->effect instanceof Properties) {
            return Normalized::properties(
                $this->effect->effects()->map($property),
            );
        }

        if ($this->effect instanceof Entity) {
            /** @psalm-suppress ImpureFunctionCall */
            return Normalized::entity(
                $this->effect->property(),
                $entity(
                    $this->effect->property(),
                    $this->effect->effects(),
                ),
            );
        }

        if ($this->effect instanceof Optional) {
            /** @psalm-suppress ImpureFunctionCall */
            return Normalized::optional(
                $this->effect->property(),
                $optional(
                    $this->effect->property(),
                    $this->effect->effects(),
                ),
            );
        }

        if ($this->effect instanceof Optional\Nothing) {
            /** @psalm-suppress ImpureFunctionCall */
            return Normalized::optionalNothing(
                $this->effect->property(),
            );
        }

        if ($this->effect instanceof Child\Add) {
            /** @psalm-suppress ImpureFunctionCall */
            return Normalized::addChildren(
                $this->effect->property(),
                $addChild(
                    $this->effect->property(),
                    $this->effect->entities(),
                ),
            );
        }

        /** @psalm-suppress ImpureFunctionCall */
        return Normalized::removeChildren(
            $this->effect->property(),
            $removeChild(
                $this->effect->property(),
                $this->effect->specification(),
            ),
        );
    }

    /**
     * @psalm-pure
     */
    private static function build(
        Properties|Entity|Optional|Optional\Nothing|Child\Add|Child\Remove $effect,
    ): self {
        return new self($effect);
    }
}
