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
    /** @var Set<Entity> */
    private Set $unmodifiedEntities;

    /**
     * @param non-empty-string $name
     * @param Set<Entity> $newEntities
     * @param Set<Entity> $unmodifiedEntities
     */
    private function __construct(
        string $name,
        Set $newEntities,
        Set $unmodifiedEntities,
    ) {
        $this->name = $name;
        $this->newEntities = $newEntities;
        $this->unmodifiedEntities = $unmodifiedEntities;
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     * @param Set<Entity> $newEntities
     */
    public static function of(string $name, Set $newEntities): self
    {
        return new self($name, $newEntities, Set::of());
    }

    /**
     * @internal
     */
    public function with(self $unmodified): self
    {
        return new self(
            $this->name,
            $this->newEntities,
            $unmodified->newEntities(),
        );
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

    /**
     * @return Set<Entity>
     */
    public function unmodifiedEntities(): Set
    {
        return $this->unmodifiedEntities;
    }

    /**
     * @return Set<Entity>
     */
    public function entities(): Set
    {
        return $this->unmodifiedEntities->merge($this->newEntities);
    }

    public function referenceSame(self $collection): bool
    {
        return $this->name === $collection->name();
    }
}
