<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Normalized;

use Innmind\Immutable\Sequence;

/**
 * @internal
 * @psalm-immutable
 */
final class Properties
{
    /**
     * @param Sequence<Property> $effects
     */
    private function __construct(
        private Sequence $effects,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param Sequence<Property> $effects
     */
    public static function of(Sequence $effects): self
    {
        return new self($effects);
    }

    /**
     * @return Sequence<Property>
     */
    public function effects(): Sequence
    {
        return $this->effects;
    }
}
