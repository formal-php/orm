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
     * @return callable(R): Attempt<R>
     */
    #[\Override]
    public function commit(): callable
    {
        return static fn(mixed $value) => Attempt::result($value);
    }

    /**
     * @template R
     *
     * @return callable(R): Attempt<R>
     */
    #[\Override]
    public function rollback(): callable
    {
        return static fn(mixed $value) => Attempt::result($value);
    }
}
