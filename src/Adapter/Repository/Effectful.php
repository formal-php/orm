<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Repository;

use Formal\ORM\Effect\Normalized;
use Innmind\Specification\Specification;

/**
 * @internal
 */
interface Effectful
{
    public function effect(
        Normalized\Properties|Normalized\Entity|Normalized\Child\Add $effect,
        ?Specification $specification,
    ): void;
}
