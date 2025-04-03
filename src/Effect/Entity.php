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
        private Property $effect,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(
        string $property,
        Property $effect,
    ): self {
        return new self($property, $effect);
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    public function effect(): Property
    {
        return $this->effect;
    }
}
