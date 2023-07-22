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
    /** @var Map<non-empty-string, Property> */
    private Map $denormalizedProperties;

    /**
     * @param non-empty-string $name
     * @param Set<Property> $properties
     */
    private function __construct(string $name, Set $properties)
    {
        $this->name = $name;
        $this->properties = $properties;
        $this->denormalizedProperties = Map::of(
            ...$properties
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );
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

    /**
     * @param non-empty-string $name
     *
     * @return Maybe<Property>
     */
    public function property(string $name): Maybe
    {
        return $this->denormalizedProperties->get($name);
    }
}
