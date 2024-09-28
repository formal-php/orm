<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Repository;

/**
 * @internal
 * @psalm-immutable
 */
final class SubMatch
{
    private mixed $value;

    private function __construct(mixed $value)
    {
        $this->value = $value;
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
