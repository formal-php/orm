<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw;

/**
 * @psalm-immutable
 */
enum Type
{
    case string;
    case int;
    case bool;
}
