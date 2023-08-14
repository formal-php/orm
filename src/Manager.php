<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\Definition\{
    Aggregates,
    Types,
};
use Innmind\Immutable\Either;

final class Manager
{
    private Adapter $adapter;
    private Aggregates $aggregates;
    private Repository\Active $repositories;
    private bool $inTransaction;

    private function __construct(Adapter $adapter, Aggregates $aggregates)
    {
        $this->adapter = $adapter;
        $this->aggregates = $aggregates;
        $this->repositories = Repository\Active::new();
        $this->inTransaction = false;
    }

    public static function of(
        Adapter $adapter,
        Aggregates $aggregates = null,
    ): self {
        return new self($adapter, $aggregates ?? Aggregates::of(Types::default()));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return Repository<T>
     */
    public function repository(string $class): Repository
    {
        return $this
            ->repositories
            ->get($class)
            ->match(
                static fn($repository) => $repository,
                function() use ($class) {
                    $definition = $this->aggregates->get($class);

                    $repository = Repository::of(
                        $this->repositories,
                        $this->adapter->repository($definition),
                        $definition,
                        fn() => $this->inTransaction,
                    );
                    $this->repositories->register($class, $repository);

                    return $repository;
                },
            );
    }

    /**
     * @template E
     * @template R
     *
     * @param callable(): Either<E, R> $transaction
     *
     * @return Either<E, R>
     */
    public function transactional(callable $transaction): Either
    {
        if ($this->inTransaction) {
            throw new \LogicException('Nested transactions not allowed');
        }

        $this->inTransaction = true;
        $transactionAdapter = $this->adapter->transaction();
        $transactionAdapter->start();

        try {
            // We force unwrapping the Either monad to prevent leaving this
            // method with a deferred Either meaning the system would have an
            // opened transaction hanging around
            return $transaction()
                ->memoize()
                ->map($transactionAdapter->commit())
                ->leftMap($transactionAdapter->rollback());
        } catch (\Throwable $e) {
            /** @psalm-suppress InvalidArgument */
            throw $transactionAdapter->rollback()($e);
        } finally {
            $this->inTransaction = false;
        }
    }
}
