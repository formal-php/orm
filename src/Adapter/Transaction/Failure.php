<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Transaction;

/**
 * @psalm-immutable
 */
final class Failure
{
    private function __construct(
        private \Throwable $e,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(\Throwable $e): self
    {
        return new self($e);
    }

    public function unwrap(): \Throwable
    {
        return $this->e;
    }
}
