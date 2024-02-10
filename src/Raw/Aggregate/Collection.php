<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

use Innmind\Immutable\Set;

/**
 * @psalm-immutable
 */
final class Collection
{
    /** @var non-empty-string */
    private string $name;
    /** @var Set<Set<Property>> */
    private Set $entities;

    /**
     * @param non-empty-string $name
     * @param Set<Set<Property>> $entities
     */
    private function __construct(string $name, Set $entities)
    {
        $this->name = $name;
        $this->entities = $entities;
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     * @param Set<Set<Property>> $entities
     */
    public static function of(string $name, Set $entities): self
    {
        return new self($name, $entities);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Set<Set<Property>>
     */
    public function entities(): Set
    {
        return $this->entities;
    }

    public function referenceSame(self $collection): bool
    {
        return $this->name === $collection->name();
    }
}
