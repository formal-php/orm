<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\Effect\{
    Child,
    Entity,
    Property,
    Property\Collection,
    Normalized,
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
     * @param callable(Sequence<Property>): Normalized\Properties $properties
     * @param callable(non-empty-string, Sequence<Property>): Normalized\Entity $entity
     * @param callable(non-empty-string, Sequence<object>): Normalized\Child\Add $addChild
     */
    public function normalize(
        callable $properties,
        callable $entity,
        callable $addChild,
    ): Normalized\Properties|Normalized\Entity|Normalized\Child\Add {
        if ($this->effect instanceof Collection) {
            /** @psalm-suppress ImpureFunctionCall */
            return $properties($this->effect->effects());
        }

        if ($this->effect instanceof Entity) {
            /** @psalm-suppress ImpureFunctionCall */
            return $entity(
                $this->effect->property(),
                $this->effect->effects(),
            );
        }

        /** @psalm-suppress ImpureFunctionCall */
        return $addChild(
            $this->effect->property(),
            $this->effect->entities(),
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
