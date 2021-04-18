<?php
declare(strict_types = 1);

namespace Formal\ORM\Manager;

use Formal\ORM\{
    Manager,
    Repository,
    Definition\Aggregate,
    SQL\Types,
    Id,
};
use Formal\AccessLayer\{
    Connection,
    Query,
};
use Innmind\Immutable\{
    Either,
    Maybe,
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
    /** @var Map<class-string, Aggregate<object>> */
    private Map $aggregates;
    /** @var Maybe<\WeakMap<Id<object>, object>> */
    private Maybe $cache;

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
        /** @var Maybe<\WeakMap<Id<object>, object>> */
        $this->cache = Maybe::nothing();

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
            fn(Id $id) => $this->lookup($id),
            fn(Id $id, object $aggregate) => $this->cache($id, $aggregate),
            fn(Id $id) => $this->invalidate($id),
            fn(): bool => $this->allowMutation,
        );
        $this->repositories[$repository] = $class;

        /** @var Repository<V> */
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
        /** @var Maybe<\WeakMap<Id<object>, object>> */
        $this->cache = Maybe::just(new \WeakMap);

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
            /** @var Maybe<\WeakMap<Id<object>, object>> */
            $this->cache = Maybe::nothing();
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

    /**
     * @template V of object
     *
     * @param Id<V> $id
     *
     * @return Maybe<V>
     */
    private function lookup(Id $id): Maybe
    {
        return $this->cache->flatMap(static function($cache) use ($id) {
            foreach ($cache as $existing => $aggregate) {
                if ($existing->equals($id)) {
                    /** @var Maybe<V> */
                    return Maybe::just($aggregate);
                }
            }

            /** @var Maybe<V> */
            return Maybe::nothing();
        });
    }

    private function cache(Id $id, object $aggregate): void
    {
        $this->cache = $this->cache->map(static function($cache) use ($id, $aggregate) {
            // the WeakMap is mutable so technically we don't need to re-assign
            // the $this->cache but we do it nonetheless to respect the immutable
            // nature of the Maybe class
            $cache[$id] = $aggregate;

            return $cache;
        });
    }

    private function invalidate(Id $id): void
    {
        $this->cache = $this->cache->map(static function($cache) use ($id) {
            // the WeakMap is mutable so technically we don't need to re-assign
            // the $this->cache but we do it nonetheless to respect the immutable
            // nature of the Maybe class
            foreach ($cache as $existing => $_) {
                if ($existing->equals($id)) {
                    unset($cache[$existing]);

                    break;
                }
            }

            return $cache;
        });
    }
}
