<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Provider;

use Formal\ORM\Effect;

/**
 * @psalm-immutable
 */
final class Entity
{
    /**
     * @param pure-Closure(Effect\Entity): Effect $build
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
     * @param pure-Closure(Effect\Entity): Effect $build
     * @param non-empty-string $property
     */
    public static function of(\Closure $build, string $property): self
    {
        return new self($build, $property);
    }

    public function properties(Properties $effects): Effect
    {
        return ($this->build)(Effect\Entity::of(
            $this->property,
            $effects->collection(),
        ));
    }
}
