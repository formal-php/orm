<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Innmind\Immutable\Either;

final class Manager
{
    private function __construct()
    {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return Repository<T>
     */
    public function repository(string $class): Repository
    {
        return Repository::of($class);
    }

    /**
     * @template E
     * @template R
     *
     * @param callable(): Either<E, R> $transaction
     *
     * @return Either<E, R>
     */
    public function transactional(callable $transaction): Either
    {
        return $transaction();
    }
}
