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
     * @param Sequence<Property> $rest
     */
    private function __construct(
        private Property $first,
        private Sequence $rest,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(Property $first, Property $second): self
    {
        return new self($first, Sequence::of($second));
    }

    public function and(Property $effect): self
    {
        return new self($this->first, ($this->rest)($effect));
    }

    /**
     * @param callable(Property): Property $map
     */
    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            $map($this->first),
            $this->rest->map($map),
        );
    }

    /**
     * @return Sequence<Property>
     */
    public function effects(): Sequence
    {
        return $this->rest->prepend(Sequence::of($this->first));
    }
}
