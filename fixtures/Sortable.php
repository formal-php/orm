<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

/**
 * @psalm-immutable
 */
final class Sortable
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-pure
     */
    public static function of(?string $value): ?self
    {
        return match ($value) {
            null => null,
            default => new self($value),
        };
    }

    public function toString(): string
    {
        return $this->value;
    }
}
