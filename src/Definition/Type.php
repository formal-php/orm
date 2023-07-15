<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

/**
 * @template D
 */
interface Type
{
    /**
     * @param ?D $value
     */
    public function normalize(mixed $value): null|string|int|bool;

    /**
     * @return ?D
     */
    public function denormalize(null|string|int|bool $value): mixed;
}
