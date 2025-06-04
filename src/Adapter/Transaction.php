<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter;

use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

interface Transaction
{
    /**
     * @return Attempt<SideEffect>
     */
    public function start(): Attempt;

    /**
     * @template R
     *
     * @return callable(R): Attempt<R>
     */
    public function commit(): callable;

    /**
     * @template R
     *
     * @return callable(R): Attempt<R>
     */
    public function rollback(): callable;
}
