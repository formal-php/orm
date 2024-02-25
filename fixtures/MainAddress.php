<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Formal\ORM\Specification\Entity2;
use Innmind\Specification\Sign;

/**
 * @psalm-immutable
 */
final class MainAddress
{
    /**
     * @psalm-pure
     */
    public static function of(Sign $sign, string $value): Entity2
    {
        return Entity2::of(
            'mainAddress',
            AddressValue::of($sign, $value),
        );
    }
}
