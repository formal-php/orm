<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter;

interface Transaction
{
    public function start(): void;

    /**
     * @template R
     *
     * @return callable(R): R
     */
    public function commit(): callable;

    /**
     * @template R
     *
     * @return callable(R): R
     */
    public function rollback(): callable;
}
