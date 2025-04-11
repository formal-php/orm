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
     */
    public function unwrap(): Collection|Entity|Child\Add
    {
        return $this->effect;
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
