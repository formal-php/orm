<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM\User;

final class Address
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function new(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
