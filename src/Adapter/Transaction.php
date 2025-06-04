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
     * @param R $value
     *
     * @return Attempt<R>
     */
    public function commit(mixed $value): Attempt;

    /**
     * @template R
     *
     * @param R $value
     *
     * @return Attempt<R>
     */
    public function rollback(mixed $value): Attempt;
}
