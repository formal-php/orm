<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Provider;

use Formal\ORM\{
    Effect,
    Effect\Provider,
    Effect\Property,
    Effect\Properties as Collection,
};
use Innmind\Immutable\Map;

/**
 * @psalm-immutable
 */
final class Properties implements Provider
{
    /**
     * @param pure-Closure(Collection): Effect $build
     * @param non-empty-string $property
     * @param Map<non-empty-string, mixed> $properties
     */
    private function __construct(
        private \Closure $build,
        private string $property,
        private mixed $value,
        private Map $properties,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param pure-Closure(Collection): Effect $build
     * @param non-empty-string $property
     */
    public static function of(
        \Closure $build,
        string $property,
        mixed $value,
    ): self {
        return new self(
            $build,
            $property,
            $value,
            Map::of(),
        );
    }

    public function and(self $other): self
    {
        return new self(
            $this->build,
            $this->property,
            $this->value,
            $this
                ->properties
                ->put($other->property, $other->value)
                ->merge($other->properties),
        );
    }

    #[\Override]
    public function toEffect(): Effect
    {
        return ($this->build)($this->collection());
    }

    /**
     * @internal
     */
    public function collection(): Collection
    {
        return $this->properties->reduce(
            Collection::of(Property::assign($this->property, $this->value)),
            static fn(Collection $properties, $property, $value) => $properties->and(
                Property::assign($property, $value),
            ),
        );
    }
}
