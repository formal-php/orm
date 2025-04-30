<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Normalized\Collection;

use Formal\ORM\Raw\Aggregate\Collection\Entity;
use Innmind\Immutable\Sequence;

/**
 * @internal
 * @psalm-immutable
 */
final class Add
{
    /**
     * @param non-empty-string $property
     * @param Sequence<Entity> $entities
     */
    private function __construct(
        private string $property,
        private Sequence $entities,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     * @param Sequence<Entity> $entities
     */
    public static function of(string $property, Sequence $entities): self
    {
        return new self($property, $entities);
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    /**
     * @return Sequence<Entity>
     */
    public function entities(): Sequence
    {
        return $this->entities;
    }
}
