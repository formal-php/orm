<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Definition\Aggregate\Identity,
    Sort as SortedBy,
    Repository\Loaded,
    Repository\Denormalize,
    Repository\Instanciate,
    Repository\Sort,
    Adapter\Repository\CrossAggregateMatching,
    Adapter\Repository\SubMatch,
    Specification\Normalize,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Maybe,
    SideEffect,
    Predicate\Instance,
};

/**
 * @template T of object
 */
final class Matching
{
    /** @var Repository<T> */
    private Repository $repository;
    /** @var Adapter\Repository<T> */
    private Adapter\Repository $adapter;
    /** @var Identity<T> */
    private Identity $identity;
    private Repository\Context $context;
    /** @var Denormalize<T> */
    private Denormalize $denormalize;
    /** @var Instanciate<T> */
    private Instanciate $instanciate;
    /** @var ?Normalize<T> */
    private ?Normalize $normalizeSpecification;
    /** @var Loaded<T> */
    private Loaded $loaded;
    /** @var Sort<T> */
    private Sort $sort;
    private ?Specification $specification;
    private null|SortedBy\Property|SortedBy\Entity $sorted;
    /** @var ?positive-int */
    private ?int $drop;
    /** @var null|0|positive-int */
    private ?int $take;

    /**
     * @param Repository<T> $repository
     * @param Adapter\Repository<T> $adapter
     * @param Identity<T> $identity
     * @param Denormalize<T> $denormalize
     * @param Instanciate<T> $instanciate
     * @param ?Normalize<T> $normalizeSpecification
     * @param Loaded<T> $loaded
     * @param Sort<T> $sort
     * @param ?positive-int $drop
     * @param null|0|positive-int $take
     */
    private function __construct(
        Repository $repository,
        Adapter\Repository $adapter,
        Identity $identity,
        Repository\Context $context,
        Denormalize $denormalize,
        Instanciate $instanciate,
        ?Normalize $normalizeSpecification,
        Loaded $loaded,
        Sort $sort,
        ?Specification $specification,
        null|SortedBy\Property|SortedBy\Entity $sorted,
        ?int $drop,
        ?int $take,
    ) {
        $this->repository = $repository;
        $this->adapter = $adapter;
        $this->identity = $identity;
        $this->context = $context;
        $this->denormalize = $denormalize;
        $this->instanciate = $instanciate;
        $this->normalizeSpecification = $normalizeSpecification;
        $this->loaded = $loaded;
        $this->sort = $sort;
        $this->specification = $specification;
        $this->sorted = $sorted;
        $this->drop = $drop;
        $this->take = $take;
    }

    /**
     * @internal
     * @template A of object
     *
     * @param Repository<A> $repository
     * @param Adapter\Repository<A> $adapter
     * @param Identity<A> $identity
     * @param Denormalize<A> $denormalize
     * @param Instanciate<A> $instanciate
     * @param Normalize<A> $normalizeSpecification
     * @param Loaded<A> $loaded
     * @param Sort<A> $sort
     *
     * @return self<A>
     */
    public static function of(
        Repository $repository,
        Adapter\Repository $adapter,
        Identity $identity,
        Repository\Context $context,
        Denormalize $denormalize,
        Instanciate $instanciate,
        Normalize $normalizeSpecification,
        Loaded $loaded,
        Sort $sort,
        Specification $specification,
    ): self {
        return new self(
            $repository,
            $adapter,
            $identity,
            $context,
            $denormalize,
            $instanciate,
            $normalizeSpecification,
            $loaded,
            $sort,
            $specification,
            null,
            null,
            null,
        );
    }

    /**
     * @internal
     * @template A of object
     *
     * @param Repository<A> $repository
     * @param Adapter\Repository<A> $adapter
     * @param Identity<A> $identity
     * @param Denormalize<A> $denormalize
     * @param Instanciate<A> $instanciate
     * @param Loaded<A> $loaded
     * @param Sort<A> $sort
     *
     * @return self<A>
     */
    public static function all(
        Repository $repository,
        Adapter\Repository $adapter,
        Identity $identity,
        Repository\Context $context,
        Denormalize $denormalize,
        Instanciate $instanciate,
        Loaded $loaded,
        Sort $sort,
    ): self {
        return new self(
            $repository,
            $adapter,
            $identity,
            $context,
            $denormalize,
            $instanciate,
            null,
            $loaded,
            $sort,
            null,
            null,
            null,
            null,
        );
    }

    /**
     * @psalm-mutation-free
     *
     * @param positive-int $size
     *
     * @return self<T>
     */
    public function take(int $size): self
    {
        if ($this->take === 0) {
            return $this;
        }

        return new self(
            $this->repository,
            $this->adapter,
            $this->identity,
            $this->context,
            $this->denormalize,
            $this->instanciate,
            $this->normalizeSpecification,
            $this->loaded,
            $this->sort,
            $this->specification,
            $this->sorted,
            $this->drop,
            match ($this->take) {
                null => $size,
                default => \min($this->take, $size),
            },
        );
    }

    /**
     * @psalm-mutation-free
     *
     * @param positive-int $size
     *
     * @return self<T>
     */
    public function drop(int $size): self
    {
        if ($this->take === 0) {
            return $this;
        }

        return new self(
            $this->repository,
            $this->adapter,
            $this->identity,
            $this->context,
            $this->denormalize,
            $this->instanciate,
            $this->normalizeSpecification,
            $this->loaded,
            $this->sort,
            $this->specification,
            $this->sorted,
            match ($this->drop) {
                null => $size,
                default => $this->drop + $size,
            },
            match ($this->take) {
                null => null,
                default => \max(0, $this->take - $size),
            },
        );
    }

    /**
     * @psalm-mutation-free
     *
     * @param non-empty-string $property
     *
     * @return self<T>
     */
    public function sort(string $property, SortedBy $direction): self
    {
        if ($this->take === 0) {
            return $this;
        }

        return new self(
            $this->repository,
            $this->adapter,
            $this->identity,
            $this->context,
            $this->denormalize,
            $this->instanciate,
            $this->normalizeSpecification,
            $this->loaded,
            $this->sort,
            $this->specification,
            ($this->sort)($property, $direction),
            $this->drop,
            $this->take,
        );
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(self<T>): self<T> $map
     *
     * @return self<T>
     */
    public function apply(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this);
    }

    /**
     * @return Sequence<T>
     */
    public function sequence(): Sequence
    {
        if ($this->take === 0) {
            return Sequence::of();
        }

        $denormalize = ($this->denormalize)();
        $specification = null;

        if ($this->normalizeSpecification && $this->specification) {
            $specification = ($this->normalizeSpecification)($this->specification);
        }

        return $this
            ->adapter
            ->fetch(
                $specification,
                $this->sorted,
                $this->drop,
                $this->take,
            )
            ->map($denormalize)
            ->map(fn($denormalized) => $this->loaded->add(
                $this->repository,
                $denormalized,
            ))
            ->map($this->instanciate);
    }

    /**
     * This method is a shortcut to ->sequence()->filter()
     *
     * @param callable(T): bool $predicate
     *
     * @return Sequence<T>
     */
    public function filter(callable $predicate): Sequence
    {
        return $this->sequence()->filter($predicate);
    }

    /**
     * This method is a shortcut to ->sequence()->exclude()
     *
     * @param callable(T): bool $predicate
     *
     * @return Sequence<T>
     */
    public function exclude(callable $predicate): Sequence
    {
        return $this->sequence()->exclude($predicate);
    }

    /**
     * This method is a shortcut to ->sequence()->foreach()
     *
     * @param callable(T): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        return $this->sequence()->foreach($function);
    }

    /**
     * This method is a shortcut to ->sequence()->first()
     *
     * @return Maybe<T>
     */
    public function first(): Maybe
    {
        return $this->sequence()->first();
    }

    /**
     * This method is a shortcut to ->sequence()->map()
     *
     * @template S
     *
     * @param callable(T): S $function
     *
     * @return Sequence<S>
     */
    public function map(callable $function): Sequence
    {
        return $this->sequence()->map($function);
    }

    /**
     * This method is a shortcut to ->sequence()->flatMap()
     *
     * @template S
     *
     * @param callable(T): Sequence<S> $map
     *
     * @return Sequence<S>
     */
    public function flatMap(callable $map): Sequence
    {
        return $this->sequence()->flatMap($map);
    }

    /**
     * This method is a shortcut to ->sequence()->reduce()
     *
     * @template I
     * @template R
     *
     * @param I $carry
     * @param callable(I|R, T): R $reducer
     *
     * @return I|R
     */
    public function reduce(mixed $carry, callable $reducer): mixed
    {
        return $this->sequence()->reduce($carry, $reducer);
    }

    /**
     * This method is a shortcut to ->sequence()->toList()
     *
     * @return list<T>
     */
    public function toList(): array
    {
        return $this->sequence()->toList();
    }

    /**
     * This method is a shortcut to ->sequence()->find()
     *
     * @param callable(T): bool $predicate
     *
     * @return Maybe<T>
     */
    public function find(callable $predicate): Maybe
    {
        return $this->sequence()->find($predicate);
    }

    /**
     * @internal
     * @psalm-mutation-free
     *
     * @return SubMatch|Sequence<Id<T>>
     */
    public function crossAggregateMatching(Repository\Context $context): SubMatch|Sequence
    {
        $take = $this->take;

        if ($take === 0) {
            return Sequence::of();
        }

        $specification = null;

        if ($this->normalizeSpecification && $this->specification) {
            $specification = ($this->normalizeSpecification)($this->specification);
        }

        /**
         * @psalm-suppress InvalidArgument Psalm doesn't understant the object passed to the id extraction for some reason
         * @var SubMatch|Sequence<Id<T>>
         */
        return Maybe::just($this->context)
            ->filter($context->same(...))
            ->map(fn() => $this->adapter)
            ->keep(Instance::of(CrossAggregateMatching::class))
            ->flatMap(fn($adapter) => $adapter->crossAggregateMatching(
                $specification,
                $this->sorted,
                $this->drop,
                $take,
            ))
            ->match(
                static fn($subQuery) => $subQuery,
                fn() => $this->map($this->identity->extract(...)),
            );
    }
}
