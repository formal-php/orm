<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

use Formal\ORM\Effect\Child\Add;

/**
 * @psalm-immutable
 */
final class Child
{
    /**
     * @param non-empty-string $property
     */
    private function __construct(
        private string $property,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(string $property): self
    {
        return new self($property);
    }

    public function add(object $entity): Add
    {
        return Add::of($this->property, $entity);
    }
}
