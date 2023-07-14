<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

final class Id
{
    private function __construct()
    {
    }

    public static function of(): self
    {
        return new self;
    }

    public function name(): string
    {
        return '';
    }

    public function value(): string
    {
        return '';
    }
}
