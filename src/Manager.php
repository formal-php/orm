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
    /** @var \WeakMap<Repository, class-string> */
    private \WeakMap $repositories;

    private function __construct(Adapter $adapter, Aggregates $aggregates)
    {
        $this->adapter = $adapter;
        $this->aggregates = $aggregates;
        /** @var \WeakMap<Repository, class-string> */
        $this->repositories = new \WeakMap;
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
        /**
         * @var Repository $repository
         * @var class-string $kind
         */
        foreach ($this->repositories as $repository => $kind) {
            if ($kind === $class) {
                /** @var Repository<T> */
                return $repository;
            }
        }

        $definition = $this->aggregates->get($class);

        $repository = Repository::of(
            $this->adapter->repository($definition),
            $definition,
        );
        $this->repositories[$repository] = $class;

        return $repository;
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
        return $transaction();
    }
}
