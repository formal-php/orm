<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type\PointInTimeType;

use Innmind\TimeContinuum\Format as FormatInterface;

/**
 * @psalm-immutable
 */
final class Format implements FormatInterface
{
    public function toString(): string
    {
        return 'Y:m:d\TH:m:i:s.uP';
    }
}
