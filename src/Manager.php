<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\Definition\{
    Aggregates,
    Types,
};
use Innmind\Immutable\{
    Either,
    Map,
    Predicate\Instance,
};

final class Manager
{
    private Adapter $adapter;
    private Aggregates $aggregates;
    /** @var Map<class-string, \WeakReference<Repository>> */
    private Map $repositories;

    private function __construct(Adapter $adapter, Aggregates $aggregates)
    {
        $this->adapter = $adapter;
        $this->aggregates = $aggregates;
        $this->repositories = Map::of();
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
        /** @var Repository<T> */
        $repository = $this
            ->repositories
            ->get($class)
            ->map(static fn($ref) => $ref->get())
            ->keep(Instance::of(Repository::class))
            ->match(
                static fn($repository) => $repository,
                function() use ($class) {
                    $definition = $this->aggregates->get($class);

                    return Repository::of(
                        $this->adapter->repository($definition),
                        $definition,
                    );
                },
            );
        $this->repositories = $this->repositories
            ->filter(static fn($_, $ref) => \is_object($ref->get())) // remove dead references
            ->put(
                $class,
                \WeakReference::create($repository),
            );

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
