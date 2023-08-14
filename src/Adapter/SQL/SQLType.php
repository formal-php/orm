<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\AccessLayer\Table\Column\Type;

/**
 * @psalm-immutable
 */
interface SQLType
{
    public function sqlType(): Type;
}
