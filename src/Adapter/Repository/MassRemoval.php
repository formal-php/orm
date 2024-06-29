<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Repository;

use Innmind\Specification\Specification;

/**
 * This interface will be merged with the Repository one in the next major version
 */
interface MassRemoval
{
    public function removeAll(Specification $specification): void;
}
