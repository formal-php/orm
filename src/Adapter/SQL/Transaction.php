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

    public function start(): void
    {
        // memoize to force unwrap the monad
        $_ = ($this->connection)(new StartTransaction)->memoize();
    }

    /**
     * @template R
     *
     * @return callable(R): R
     */
    public function commit(): callable
    {
        $connection = $this->connection;

        return static function(mixed $value) use ($connection) {
            // memoize to force unwrap the monad
            $_ = $connection(new Commit)->memoize();

            return $value;
        };
    }

    /**
     * @template R
     *
     * @return callable(R): R
     */
    public function rollback(): callable
    {
        $connection = $this->connection;

        return static function(mixed $value) use ($connection) {
            // memoize to force unwrap the monad
            $_ = $connection(new Rollback)->memoize();

            return $value;
        };
    }
}
