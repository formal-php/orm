<?php
declare(strict_types = 1);

namespace Formal\ORM\Manager;

use Formal\ORM\{
    Manager,
    Repository,
};
use Innmind\Immutable\{
    Either,
    Map,
};

final class InMemory implements Manager
{
    private bool $allowMutation = false;
    /** @var Map<class-string, Repository\InMemory<object>> */
    private Map $repositories;

    public function __construct()
    {
        /** @var Map<class-string, Repository\InMemory<object>> */
        $this->repositories = Map::of('string', Repository\InMemory::class);
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
        if ($this->repositories->contains($class)) {
            /** @var Repository<V> */
            return $this->repositories->get($class);
        }

        $repository = new Repository\InMemory(
            $class,
            fn(): bool => $this->allowMutation,
        );
        $this->repositories = ($this->repositories)($class, $repository);

        return $repository;
    }

    /**
     * @template L of \Throwable
     * @template R
     *
     * @param callable(): Either<L, R> $transaction
     *
     * @return Either<L, R>
     */
    public function transactional(callable $transaction): Either
    {
        $this->allowMutation = true;

        try {
            /** @var Either<L, R> */
            return $transaction()
                ->map(function(mixed $value): mixed {
                    $this->commit();

                    return $value;
                })
                ->leftMap(function(\Throwable $error): \Throwable {
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

    private function commit(): void
    {
        $this
            ->repositories
            ->values()
            ->foreach(static fn($repository) => $repository->commit());
    }

    private function rollback(): void
    {
        $this
            ->repositories
            ->values()
            ->foreach(static fn($repository) => $repository->rollback());
    }
}
