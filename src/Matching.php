<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Adapter,
    Repository,
    Sort as SortedBy,
    Repository\Loaded,
    Repository\Denormalize,
    Repository\Instanciate,
    Repository\Sort,
    Specification\Normalize,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\Sequence;

/**
 * @template T of object
 */
final class Matching
{
    /** @var Repository<T> */
    private Repository $repository;
    /** @var Adapter\Repository<T> */
    private Adapter\Repository $adapter;
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
    /** @var ?positive-int */
    private ?int $take;

    /**
     * @param Repository<T> $repository
     * @param Adapter\Repository<T> $adapter
     * @param Denormalize<T> $denormalize
     * @param Instanciate<T> $instanciate
     * @param ?Normalize<T> $normalizeSpecification
     * @param Loaded<T> $loaded
     * @param Sort<T> $sort
     * @param ?positive-int $drop
     * @param ?positive-int $take
     */
    private function __construct(
        Repository $repository,
        Adapter\Repository $adapter,
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
     * @template A of object
     *
     * @param Repository<A> $repository
     * @param Adapter\Repository<A> $adapter
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
     * @template A of object
     *
     * @param Repository<A> $repository
     * @param Adapter\Repository<A> $adapter
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
        Denormalize $denormalize,
        Instanciate $instanciate,
        Loaded $loaded,
        Sort $sort,
    ): self {
        return new self(
            $repository,
            $adapter,
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
        return new self(
            $this->repository,
            $this->adapter,
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
        return new self(
            $this->repository,
            $this->adapter,
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
            $this->take,
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
        return new self(
            $this->repository,
            $this->adapter,
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
    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this);
    }

    /**
     * @return Sequence<T>
     */
    public function fetch(): Sequence
    {
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
}
