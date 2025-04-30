<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

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
     */
    public static function of(Property $effect): self
    {
        return new self(Sequence::of($effect));
    }

    public function and(Property $effect): self
    {
        return new self(($this->effects)($effect));
    }

    /**
     * @return Sequence<Property>
     */
    public function effects(): Sequence
    {
        return $this->effects;
    }
}
