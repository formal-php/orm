<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Adapter\Transaction as TransactionInterface;

/**
 * @internal
 */
final class Transaction implements TransactionInterface
{
    private function __construct()
    {
    }

    /**
     * @internal
     */
    public static function of(): self
    {
        return new self;
    }

    #[\Override]
    public function start(): void
    {
    }

    /**
     * @template R
     *
     * @return callable(R): R
     */
    #[\Override]
    public function commit(): callable
    {
        return static function(mixed $value) {
            return $value;
        };
    }

    /**
     * @template R
     *
     * @return callable(R): R
     */
    #[\Override]
    public function rollback(): callable
    {
        return static function(mixed $value) {
            return $value;
        };
    }
}
