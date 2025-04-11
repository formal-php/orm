<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Provider;

use Formal\ORM\{
    Effect,
    Effect\Child\Add,
};

/**
 * @psalm-immutable
 */
final class Child
{
    /**
     * @param pure-Closure(Add): Effect $build
     * @param non-empty-string $property
     */
    private function __construct(
        private \Closure $build,
        private string $property,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param pure-Closure(Add): Effect $build
     * @param non-empty-string $property
     */
    public static function of(\Closure $build, string $property): self
    {
        return new self($build, $property);
    }

    public function add(object $entity): Effect
    {
        return ($this->build)(Add::of(
            $this->property,
            $entity,
        ));
    }
}
