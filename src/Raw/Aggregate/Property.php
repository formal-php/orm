<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

use Formal\ORM\Raw\Type;

final class Property
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

    public function value(): mixed
    {
        return null;
    }

    public function type(): Type
    {
        return Type::string;
    }
}
