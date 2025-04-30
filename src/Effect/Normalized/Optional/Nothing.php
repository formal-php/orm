<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Normalized\Optional;

/**
 * @internal
 * @psalm-immutable
 */
final class Nothing
{
    /**
     * @param non-empty-string $property
     */
    private function __construct(
        private string $property,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(string $property): self
    {
        return new self($property);
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }
}
