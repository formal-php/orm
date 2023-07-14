<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Definition\Aggregate,
    Adapter\Repository,
};

interface Adapter
{
    /**
     * @template T of object
     *
     * @param Aggregate<T> $definition
     *
     * @return Repository<T>
     */
    public function repository(Aggregate $definition): Repository;

    // TODO transactions
}
