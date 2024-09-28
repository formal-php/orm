<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

/**
 * This object is here to know if 2 repositories come from the same manager.
 *
 * This is used for the cross aggregate search feature that needs both
 * repositories to be in the same context to optimise the search.
 *
 * @internal
 */
final class Context
{
    public function same(self $other): bool
    {
        return $this === $other;
    }
}
