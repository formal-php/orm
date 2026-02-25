<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\Adapter\Transaction as TransactionInterface;
use Formal\AccessLayer\{
    Connection,
    Query\Transaction as Query,
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
    private function __construct(private Connection $connection)
    {
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
            fn() => ($this->connection)(Query::start)->memoize(),
        )->map(static fn() => SideEffect::identity());
    }

    /**
     * @template R
     *
     * @param R $value
     *
     * @return Attempt<R>
     */
    #[\Override]
    public function commit(mixed $value): Attempt
    {
        $connection = $this->connection;

        return Attempt::of(
            // memoize to force unwrap the monad
            static fn() =>  $connection(Query::commit)->memoize(),
        )->map(static fn() => $value);
    }

    /**
     * @template R
     *
     * @param R $value
     *
     * @return Attempt<R>
     */
    #[\Override]
    public function rollback(mixed $value): Attempt
    {
        $connection = $this->connection;

        return Attempt::of(
            // memoize to force unwrap the monad
            static fn() =>  $connection(Query::rollback)->memoize(),
        )->map(static fn() => $value);
    }
}
