<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

use Formal\ORM\{
    Effect\Normalized\Properties,
    Effect\Normalized\Property,
    Effect\Normalized\Entity,
    Effect\Normalized\Child,
    Raw\Aggregate\Collection\Entity as RawEntity,
};
use Innmind\Immutable\Sequence;

/**
 * @internal
 * @psalm-immutable
 */
final class Normalized
{
    private function __construct(
        private Properties|Entity|Child\Add $effect,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param Sequence<Property> $effects
     */
    public static function properties(Sequence $effects): self
    {
        return new self(Properties::of($effects));
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $entity
     * @param Sequence<Property> $effects
     */
    public static function entity(string $entity, Sequence $effects): self
    {
        return new self(Entity::of(
            $entity,
            Properties::of($effects),
        ));
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $collection
     * @param Sequence<RawEntity> $effects
     */
    public static function addChildren(string $collection, Sequence $effects): self
    {
        return new self(Child\Add::of(
            $collection,
            $effects,
        ));
    }

    /**
     * @template R
     *
     * @param callable(Sequence<Property>): R $properties
     * @param callable(non-empty-string, Sequence<Property>): R $entity
     * @param callable(non-empty-string, Sequence<RawEntity>): R $addChild
     *
     * @return R
     */
    public function match(
        callable $properties,
        callable $entity,
        callable $addChild,
    ): mixed {
        if ($this->effect instanceof Properties) {
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
}
