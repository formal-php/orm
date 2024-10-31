<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

/**
 * @template D
 * @psalm-immutable
 */
interface Type
{
    /**
     * @param D $value
     */
    public function normalize(mixed $value): null|string|int|float|bool;

    /**
     * @return D
     */
    public function denormalize(null|string|int|float|bool $value): mixed;
}
