<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter;

use Formal\ORM\{
    Adapter,
    Definition\Aggregate,
};
use Formal\AccessLayer\Connection;

final class SQL implements Adapter
{
    private Connection $connection;
    private SQL\Transaction $transaction;

    private function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->transaction = SQL\Transaction::of($connection);
    }

    public static function of(Connection $connection): self
    {
        return new self($connection);
    }

    public function repository(Aggregate $definition): Repository
    {
        return SQL\Repository::of(
            $this->connection,
            $definition,
        );
    }

    public function transaction(): Transaction
    {
        return $this->transaction;
    }
}
