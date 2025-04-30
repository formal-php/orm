<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Provider;

use Formal\ORM\{
    Effect,
    Effect\Properties as Collection,
};

/**
 * @psalm-immutable
 */
final class Property
{
    /**
     * @param pure-Closure(Collection): Effect $build
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
     * @param pure-Closure(Collection): Effect $build
     * @param non-empty-string $property
     */
    public static function of(\Closure $build, string $property): self
    {
        return new self($build, $property);
    }

    public function assign(mixed $value): Properties
    {
        return Properties::of($this->build, $this->property, $value);
    }
}
