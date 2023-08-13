<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Repository,
    Id,
};
use Innmind\Immutable\Maybe;

final class Active
{
    /** @var \WeakMap<Repository, class-string> */
    private \WeakMap $repositories;
    /** @var \WeakMap<Id, Repository> */
    private \WeakMap $active;

    private function __construct()
    {
        /** @var \WeakMap<Repository, class-string> */
        $this->repositories = new \WeakMap;
        /** @var \WeakMap<Id, Repository> */
        $this->active = new \WeakMap;
    }

    public static function new(): self
    {
        return new self;
    }

    /**
     * @param class-string $class
     */
    public function register(string $class, Repository $repository): void
    {
        $this->repositories[$repository] = $class;
    }

    /**
     * @param class-string $class
     */
    public function active(string $class, Id $id): void
    {
        $_ = $this->get($class)->match(
            fn($repository) => $this->active[$id] = $repository,
            static fn() => null,
        );
    }

    public function forget(Id $id): void
    {
        $this->active->offsetUnset($id);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return Maybe<Repository<T>>
     */
    public function get(string $class): Maybe
    {
        /**
         * @var Repository $repository
         * @var class-string $kind
         */
        foreach ($this->repositories as $repository => $kind) {
            if ($kind === $class) {
                /** @var Maybe<Repository<T>> */
                return Maybe::just($repository);
            }
        }

        /** @var Maybe<Repository<T>> */
        return Maybe::nothing();
    }
}
