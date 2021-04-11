<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Innmind\Immutable\Either;

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
     * @template L of \Throwable
     * @template R
     *
     * @param callable(): Either<L, R> $transaction
     *
     * @return Either<L, R>
     */
    public function transactional(callable $transaction): Either;
}
