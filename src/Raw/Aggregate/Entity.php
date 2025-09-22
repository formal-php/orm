<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Entity
{
    /**
     * @param non-empty-string $name
     * @param Sequence<Property> $properties
     */
    private function __construct(
        private string $name,
        private Sequence $properties,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     * @param Sequence<Property> $properties
     */
    public static function of(string $name, Sequence $properties): self
    {
        return new self($name, $properties);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Sequence<Property>
     */
    public function properties(): Sequence
    {
        return $this->properties;
    }
}
