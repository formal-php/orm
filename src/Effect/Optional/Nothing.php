<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Optional;

/**
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
     * @psalm-pure
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
