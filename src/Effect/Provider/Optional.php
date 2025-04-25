<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Provider;

use Formal\ORM\{
    Effect,
    Effect\Optional\Nothing,
};

/**
 * @psalm-immutable
 */
final class Optional
{
    /**
     * @param pure-Closure(Nothing): Effect $build
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
     * @param pure-Closure(Nothing): Effect $build
     * @param non-empty-string $property
     */
    public static function of(\Closure $build, string $property): self
    {
        return new self($build, $property);
    }

    public function nothing(): Effect
    {
        return ($this->build)(Nothing::of($this->property));
    }
}
