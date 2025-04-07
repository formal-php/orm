<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

/**
 * @psalm-immutable
 */
final class Entity
{
    /**
     * @param non-empty-string $property
     */
    private function __construct(
        private string $property,
        private Property\Collection $effects,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(
        string $property,
        Property|Property\Collection $effect,
    ): self {
        if ($effect instanceof Property) {
            $effect = Property\Collection::of($effect);
        }

        return new self($property, $effect);
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    public function effects(): Property\Collection
    {
        return $this->effects;
    }
}
