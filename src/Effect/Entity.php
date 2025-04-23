<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

use Innmind\Immutable\Sequence;

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

    /**
     * @return Sequence<Property>
     */
    public function effects(): Sequence
    {
        return $this->effects->effects();
    }
}
