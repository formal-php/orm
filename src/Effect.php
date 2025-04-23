<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\Effect\{
    Child,
    Entity,
    Property\Collection,
};

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
     * @template R
     *
     * @param callable(Collection): R $collection
     * @param callable(Entity): R $entity
     * @param callable(Child\Add): R $addChild
     *
     * @return R
     */
    public function match(
        callable $collection,
        callable $entity,
        callable $addChild,
    ): mixed {
        if ($this->effect instanceof Collection) {
            /** @psalm-suppress ImpureFunctionCall */
            return $collection($this->effect);
        }

        if ($this->effect instanceof Entity) {
            /** @psalm-suppress ImpureFunctionCall */
            return $entity($this->effect);
        }

        /** @psalm-suppress ImpureFunctionCall */
        return $addChild($this->effect);
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
