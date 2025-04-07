<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Property;

use Formal\ORM\Effect\Property;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Collection
{
    /**
     * @param Sequence<Property> $effects
     */
    private function __construct(
        private Sequence $effects,
    ) {
    }

    /**
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
     * @param callable(Property): Property $map
     */
    public function map(callable $map): self
    {
        return new self(
            $this->effects->map($map),
        );
    }

    /**
     * @return Sequence<Property>
     */
    public function effects(): Sequence
    {
        return $this->effects;
    }
}
