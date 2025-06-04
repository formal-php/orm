<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\Adapter\Transaction as TransactionInterface;
use Formal\AccessLayer\{
    Connection,
    Query\StartTransaction,
    Query\Commit,
    Query\Rollback,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @internal
 */
final class Transaction implements TransactionInterface
{
    private Connection $connection;

    private function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @internal
     */
    public static function of(Connection $connection): self
    {
        return new self($connection);
    }

    #[\Override]
    public function start(): Attempt
    {
        return Attempt::of(
            // memoize to force unwrap the monad
            fn() => ($this->connection)(new StartTransaction)->memoize(),
        )->map(static fn() => SideEffect::identity());
    }

    /**
     * @template R
     *
     * @return callable(R): Attempt<R>
     */
    #[\Override]
    public function commit(): callable
    {
        $connection = $this->connection;

        return static fn(mixed $value) => Attempt::of(
            // memoize to force unwrap the monad
            static fn() =>  $connection(new Commit)->memoize(),
        )->map(static fn() => $value);
    }

    /**
     * @template R
     *
     * @return callable(R): Attempt<R>
     */
    #[\Override]
    public function rollback(): callable
    {
        $connection = $this->connection;

        return static fn(mixed $value) => Attempt::of(
            // memoize to force unwrap the monad
            static fn() =>  $connection(new Rollback)->memoize(),
        )->map(static fn() => $value);
    }
}
