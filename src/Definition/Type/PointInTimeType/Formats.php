<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Type\PointInTimeType;

use Innmind\TimeContinuum\{
    Format,
    Format\Custom
};

/**
 * @psalm-immutable
 */
enum Formats implements Custom
{
    case default;

    #[\Override]
    public function normalize(): Format
    {
        return Format::of('Y-m-d\TH:i:s.uP');
    }
}
