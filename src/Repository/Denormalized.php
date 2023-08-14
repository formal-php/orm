<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\Id;
use Innmind\Immutable\Map;

/**
 * @internal
 * @psalm-immutable
 * @template T of object
 */
final class Denormalized
{
    /** @var Id<T> */
    private Id $id;
    /** @var Map<non-empty-string, mixed> */
    private Map $properties;

    /**
     * @param Id<T> $id
     * @param Map<non-empty-string, mixed> $properties
     */
    private function __construct(Id $id, Map $properties)
    {
        $this->id = $id;
        $this->properties = $properties;
    }

    /**
     * @internal
     * @psalm-pure
     * @template A of object
     *
     * @param Id<A> $id
     * @param Map<non-empty-string, mixed> $properties
     *
     * @return self<A>
     */
    public static function of(Id $id, Map $properties): self
    {
        return new self($id, $properties);
    }

    /**
     * @return Id<T>
     */
    public function id(): Id
    {
        return $this->id;
    }

    /**
     * @return Map<non-empty-string, mixed>
     */
    public function properties(): Map
    {
        return $this->properties;
    }
}
