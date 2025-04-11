<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

use Formal\ORM\Effect;

/**
 * @internal
 */
interface Provider
{
    public function toEffect(): Effect;
}
