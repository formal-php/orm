<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter;

use Formal\ORM\{
    Adapter,
    Definition\Aggregate,
};
use Innmind\Filesystem\Adapter as Storage;

final class Filesystem implements Adapter
{
    private function __construct(private Filesystem\Transaction $transaction)
    {
    }

    public static function of(Storage $adapter): self
    {
        return new self(Filesystem\Transaction::of($adapter));
    }

    #[\Override]
    public function repository(Aggregate $definition): Repository
    {
        return Filesystem\Repository::of(
            $this->transaction,
            $definition,
        );
    }

    #[\Override]
    public function transaction(): Transaction
    {
        return $this->transaction;
    }
}
