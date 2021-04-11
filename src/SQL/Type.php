<?php
declare(strict_types = 1);

namespace Formal\ORM\SQL;

use Formal\ORM\Definition\Property;
use Formal\AccessLayer\Table\Column;

/**
 * @template T
 * @template V
 */
interface Type
{
    /**
     * @param T $value
     *
     * @return V
     */
    public function normalize(mixed $value): mixed;

    /**
     * @param V $value
     *
     * @return T
     */
    public function denormalize(mixed $value): mixed;
    public function declaration(Property $property): Column;

    /**
     * String representation of the type in PHP (ie: int, ?int, SomeClass, etc...)
     */
    public function type(): string;
}
