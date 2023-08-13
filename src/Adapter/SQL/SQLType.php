<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\AccessLayer\Table\Column\Type;

interface SQLType
{
    public function sqlType(): Type;
}
