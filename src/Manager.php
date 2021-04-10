<?php
declare(strict_types = 1);

namespace Formal\ORM;

interface Manager
{
    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return Repository<T>
     */
    public function repository(string $class): Repository;

    /**
     * @param callable(): void $transaction
     */
    public function transactional(callable $transaction): void;
}
