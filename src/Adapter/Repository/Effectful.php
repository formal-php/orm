<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Repository;

use Formal\ORM\Effect\Normalized;
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @internal
 */
interface Effectful
{
    /**
     * @return Attempt<SideEffect>
     */
    public function effect(
        Normalized $effect,
        ?Specification $specification,
    ): Attempt;
}
