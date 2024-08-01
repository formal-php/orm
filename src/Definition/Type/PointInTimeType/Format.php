<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type\PointInTimeType;

use Innmind\TimeContinuum\Format as FormatInterface;

/**
 * @psalm-immutable
 */
final class Format implements FormatInterface
{
    /**
     * This is a non standard format (stupid mistake) but is kept as is until
     * the next major release. The ::new named constructor should be used to
     * have the correct format (that will be the future default).
     */
    private string $format = 'Y:m:d\TH:i:s.uP';

    public static function new(): self
    {
        $self = new self;
        $self->format = 'Y-m-d\TH:i:s.uP';

        return $self;
    }

    public function toString(): string
    {
        return $this->format;
    }
}
