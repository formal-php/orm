<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

use Formal\ORM\Effect\Property\Collection;

/**
 * @psalm-immutable
 */
final class Property
{
    /**
     * @param non-empty-string $property
     */
    private function __construct(
        private string $property,
        private mixed $value,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function assign(
        string $property,
        mixed $value,
    ): self {
        return new self($property, $value);
    }

    public function and(self $effect): Collection
    {
        return Collection::of($this)->and($effect);
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    public function value(): mixed
    {
        return $this->value;
    }
}
