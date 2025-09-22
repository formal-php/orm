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
    /**
     * @param non-empty-string $name
     * @param Set<Entity> $entities
     */
    private function __construct(
        private string $name,
        private Set $entities,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     * @param Set<Entity> $entities
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
     * @return Set<Entity>
     */
    public function entities(): Set
    {
        return $this->entities;
    }
}
