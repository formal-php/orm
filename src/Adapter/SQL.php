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
    /** @var callable(): Connection  */
    private $establish;
    /** @var \WeakReference<Connection> */
    private \WeakReference $established;

    /**
     * @param callable(): Connection $establish
     */
    private function __construct(callable $establish)
    {
        $this->establish = $establish;
        /**
         * Using an unassigned object creates an empty WeakReference
         * @var \WeakReference<Connection>
         */
        $this->established = \WeakReference::create(new \stdClass);
    }

    public static function of(Connection $connection): self
    {
        return new self(static fn() => $connection);
    }

    /**
     * @param callable(): Connection $establish
     */
    public static function lazy(callable $establish): self
    {
        return new self($establish);
    }

    public function repository(Aggregate $definition): Repository
    {
        return SQL\Repository::of(
            $this->connection(),
            $definition,
        );
    }

    public function transaction(): Transaction
    {
        return SQL\Transaction::of($this->connection());
    }

    private function connection(): Connection
    {
        $connection = $this->established->get();

        if ($connection) {
            return $connection;
        }

        $connection = ($this->establish)();
        $this->established = \WeakReference::create($connection);

        return $connection;
    }
}
