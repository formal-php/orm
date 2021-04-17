<?php
declare(strict_types = 1);

namespace Formal\ORM\Manager;

use Formal\ORM\{
    Manager,
    Repository,
    Definition\Aggregate,
    SQL\Types,
};
use Formal\AccessLayer\{
    Connection,
    Query,
};
use Innmind\Immutable\{
    Either,
    Map,
    Exception\ElementNotFound,
};

final class SQL implements Manager
{
    private Connection $connection;
    private Types $types;
    private bool $allowMutation = false;
    /** @var \WeakMap<Repository\SQL<object>, class-string> */
    private \WeakMap $repositories;
    /** @var Map<class-string, Aggregate> */
    private Map $aggregates;

    private function __construct(
        Connection $connection,
        Types $types,
        Aggregate ...$aggregates
    ) {
        $this->connection = $connection;
        $this->types = $types;
        /** @var \WeakMap<Repository\SQL<object>, class-string> */
        $this->repositories = new \WeakMap;
        $this->aggregates = Map::of('string', Aggregate::class);

        foreach ($aggregates as $aggregate) {
            $this->aggregates = ($this->aggregates)($aggregate->class(), $aggregate);
        }
    }

    public static function of(
        Connection $connection,
        Types $types,
        Aggregate ...$aggregates
    ): self {
        return new self($connection, $types, ...$aggregates);
    }

    /**
     * @template V of object
     *
     * @param class-string<V> $class
     *
     * @return Repository<V>
     */
    public function repository(string $class): Repository
    {
        foreach ($this->repositories as $repository => $aggregate) {
            if ($class === $aggregate) {
                /** @var Repository<V> */
                return $repository;
            }
        }

        $repository = new Repository\SQL(
            $class,
            $this->aggregate($class),
            $this->connection,
            $this->types,
            fn(): bool => $this->allowMutation,
        );
        $this->repositories[$repository] = $class;

        return $repository;
    }

    /**
     * @template L
     * @template R
     *
     * @param callable(): Either<L, R> $transaction
     *
     * @return Either<L, R>
     */
    public function transactional(callable $transaction): Either
    {
        ($this->connection)(new Query\StartTransaction);
        $this->allowMutation = true;

        try {
            /** @var Either<L, R> */
            return $transaction()
                ->map(function(mixed $value): mixed {
                    $this->commit();

                    return $value;
                })
                ->leftMap(function(mixed $error): mixed {
                    $this->rollback();

                    return $error;
                });
        } catch (\Throwable $e) {
            $this->rollback();

            throw $e;
        } finally {
            $this->allowMutation = false;
        }
    }

    /**
     * @param class-string $class
     */
    private function aggregate(string $class): Aggregate
    {
        try {
            return $this->aggregates->get($class);
        } catch (ElementNotFound $e) {
            return Aggregate::of($class);
        }
    }

    private function commit(): void
    {
        ($this->connection)(new Query\Commit);
    }

    private function rollback(): void
    {
        ($this->connection)(new Query\Rollback);
    }
}
