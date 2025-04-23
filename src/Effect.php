<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Effect\Child,
    Effect\Entity,
    Effect\Property,
    Effect\Property\Collection,
    Effect\Normalized,
    Raw\Aggregate\Collection\Entity as RawEntity,
};
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Effect
{
    private function __construct(
        private Collection|Entity|Child\Add $effect,
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
     * @param callable(non-empty-string, Sequence<object>): Sequence<RawEntity> $addChild
     */
    public function normalize(
        callable $property,
        callable $entity,
        callable $addChild,
    ): Normalized\Properties|Normalized\Entity|Normalized\Child\Add {
        if ($this->effect instanceof Collection) {
            return Normalized\Properties::of($this->effect->effects()->map($property));
        }

        if ($this->effect instanceof Entity) {
            /** @psalm-suppress ImpureFunctionCall */
            return Normalized\Entity::of(
                $this->effect->property(),
                Normalized\Properties::of(
                    $entity(
                        $this->effect->property(),
                        $this->effect->effects(),
                    ),
                ),
            );
        }

        /** @psalm-suppress ImpureFunctionCall */
        return Normalized\Child\Add::of(
            $this->effect->property(),
            $addChild(
                $this->effect->property(),
                $this->effect->entities(),
            ),
        );
    }

    /**
     * @psalm-pure
     */
    private static function build(
        Collection|Entity|Child\Add $effect,
    ): self {
        return new self($effect);
    }
}
