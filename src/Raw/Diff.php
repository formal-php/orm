<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw;

use Innmind\Immutable\{
    Set,
    Map,
    Maybe,
};

final class Diff
{
    private Aggregate\Id $id;
    /** @var Set<Aggregate\Property> */
    private Set $properties;
    /** @var Map<non-empty-string, Aggregate\Property> */
    private Map $denormalizedProperties;

    /**
     * @param Set<Aggregate\Property> $properties
     */
    private function __construct(Aggregate\Id $id, Set $properties)
    {
        $this->id = $id;
        $this->properties = $properties;
        $this->denormalizedProperties = Map::of(
            ...$properties
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );
    }

    /**
     * @param Set<Aggregate\Property> $properties
     */
    public static function of(Aggregate\Id $id, Set $properties): self
    {
        return new self($id, $properties);
    }

    public function id(): Aggregate\Id
    {
        return $this->id;
    }

    /**
     * @return Set<Aggregate\Property>
     */
    public function properties(): Set
    {
        return $this->properties;
    }

    /**
     * @param non-empty-string $name
     *
     * @return Maybe<Aggregate\Property>
     */
    public function property(string $name): Maybe
    {
        return $this->denormalizedProperties->get($name);
    }
}
