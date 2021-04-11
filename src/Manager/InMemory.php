<?php
declare(strict_types = 1);

namespace Formal\ORM\Manager;

use Formal\ORM\{
    Manager,
    Repository,
};
use Innmind\Immutable\Map;

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

    public function transactional(callable $transaction): void
    {
        try {
            $this->allowMutation = true;
            $transaction();
            $this->commit();
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
