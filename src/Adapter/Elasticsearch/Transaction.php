<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Adapter\Transaction as TransactionInterface;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

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
    public function start(): Attempt
    {
        return Attempt::result(SideEffect::identity());
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
        return Attempt::result($value);
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
        return Attempt::result($value);
    }
}
