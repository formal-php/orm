<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Normalized;

use Innmind\Immutable\Sequence;

/**
 * @internal
 * @psalm-immutable
 */
final class Entity
{
    /**
     * @param non-empty-string $property
     */
    private function __construct(
        private string $property,
        private Properties $effects,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(
        string $property,
        Properties $effects,
    ): self {
        return new self($property, $effects);
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
