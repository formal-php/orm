<?php
declare(strict_types = 1);

namespace Formal\ORM\Specification;

use Innmind\Specification\{
    Specification,
    Composable,
};

/**
 * @psalm-immutable
 */
final class Entity implements Specification
{
    use Composable;

    /** @var non-empty-string */
    private string $entity;
    private Specification $specification;

    /**
     * @param non-empty-string $entity
     */
    private function __construct(string $entity, Specification $specification)
    {
        $this->entity = $entity;
        $this->specification = $specification;
    }

    /**
     * Use this specification to find an aggregate where the specified entity
     * matches the given specification.
     *
     * @psalm-pure
     *
     * @param non-empty-string $entity
     */
    public static function of(string $entity, Specification $specification): self
    {
        return new self($entity, $specification);
    }

    /**
     * @return non-empty-string
     */
    public function entity(): string
    {
        return $this->entity;
    }

    public function specification(): Specification
    {
        return $this->specification;
    }
}
