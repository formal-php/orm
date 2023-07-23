<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

use Innmind\Immutable\{
    Set,
    Map,
    Maybe,
};

final class Entity
{
    /** @var non-empty-string */
    private string $name;
    /** @var Set<Property> */
    private Set $properties;

    /**
     * @param non-empty-string $name
     * @param Set<Property> $properties
     */
    private function __construct(string $name, Set $properties)
    {
        $this->name = $name;
        $this->properties = $properties;
    }

    /**
     * @param non-empty-string $name
     * @param Set<Property> $properties
     */
    public static function of(string $name, Set $properties): self
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
     * @return Set<Property>
     */
    public function properties(): Set
    {
        return $this->properties;
    }

    public function referenceSame(self $entity): bool
    {
        return $this->name === $entity->name();
    }
}
