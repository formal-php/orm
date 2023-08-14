<?php
declare(strict_types = 1);

namespace Formal\ORM;

/**
 * @psalm-immutable
 */
enum Sort
{
    case asc;
    case desc;
}
