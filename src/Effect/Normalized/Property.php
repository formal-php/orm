<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Normalized;

/**
 * @internal
 * @psalm-immutable
 */
final class Property
{
    /**
     * @param non-empty-string $property
     */
    private function __construct(
        private string $property,
        private null|string|int|float|bool $value,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function assign(
        string $property,
        null|string|int|float|bool $value,
    ): self {
        return new self($property, $value);
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    public function value(): null|string|int|float|bool
    {
        return $this->value;
    }
}
