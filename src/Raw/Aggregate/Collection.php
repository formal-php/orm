<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

use Formal\ORM\Raw\Aggregate\Collection\Entity;
use Innmind\Immutable\Set;

/**
 * @psalm-immutable
 */
final class Collection
{
    /** @var non-empty-string */
    private string $name;
    /** @var Set<Entity> */
    private Set $newEntities;

    /**
     * @param non-empty-string $name
     * @param Set<Entity> $newEntities
     */
    private function __construct(string $name, Set $newEntities)
    {
        $this->name = $name;
        $this->newEntities = $newEntities;
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     * @param Set<Entity> $newEntities
     */
    public static function of(string $name, Set $newEntities): self
    {
        return new self($name, $newEntities);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Set<Entity>
     */
    public function newEntities(): Set
    {
        return $this->newEntities;
    }

    public function referenceSame(self $collection): bool
    {
        return $this->name === $collection->name();
    }
}
