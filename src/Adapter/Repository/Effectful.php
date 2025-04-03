<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Repository;

use Formal\ORM\Effect;
use Innmind\Specification\Specification;

/**
 * @internal
 */
interface Effectful
{
    public function effect(
        Effect\Property|Effect\Collection $effect,
        ?Specification $specification,
    ): void;
}
