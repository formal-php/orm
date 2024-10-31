<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

/**
 * @psalm-immutable
 */
final class CreatedAt
{
    private float $value;

    public function __construct(float $value)
    {
        $this->value = $value;
    }

    public function toFloat(): float
    {
        return $this->value;
    }
}
