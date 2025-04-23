<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

use Formal\ORM\{
    Effect\Normalized\Properties,
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
     * @param Sequence<Normalized\Property> $effects
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
     * @param Sequence<Normalized\Property> $effects
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

    public function unwrap(): Properties|Entity|Child\Add
    {
        return $this->effect;
    }
}
