<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Repository;

/**
 * @internal
 * @psalm-immutable
 */
final class SubMatch
{
    private function __construct(private mixed $value)
    {
    }

    /**
     * @psalm-pure
     */
    public static function of(mixed $value): self
    {
        return new self($value);
    }

    public function unwrap(): mixed
    {
        return $this->value;
    }
}
