<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Child;

use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Add
{
    /**
     * @param non-empty-string $property
     * @param Sequence<object> $entities
     */
    private function __construct(
        private string $property,
        private Sequence $entities,
    ) {
    }

    /**
     * @psalm-pure
     * @param non-empty-string $property
     */
    public static function of(string $property, object $entity): self
    {
        return new self($property, Sequence::of($entity));
    }

    public function add(object $entity): self
    {
        return new self($this->property, ($this->entities)($entity));
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    /**
     * @return Sequence<object>
     */
    public function entities(): Sequence
    {
        return $this->entities;
    }
}
