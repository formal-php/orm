<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Adapter\Repository,
    Repository\Loaded,
    Repository\Denormalize,
    Repository\Instanciate,
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
    /** @var Denormalize<T> */
    private Denormalize $denormalize;
    /** @var Instanciate<T> */
    private Instanciate $instanciate;
    /** @var ?Normalize<T> */
    private ?Normalize $normalizeSpecification;
    /** @var Loaded<T> */
    private Loaded $loaded;
    private ?Specification $specification;
    /** @var ?array{non-empty-string, Sort} */
    private ?array $sort;
    /** @var ?positive-int */
    private ?int $drop;
    /** @var ?positive-int */
    private ?int $take;

    /**
     * @param Repository<T> $repository
     * @param Denormalize<T> $denormalize
     * @param Instanciate<T> $instanciate
     * @param ?Normalize<T> $normalizeSpecification
     * @param Loaded<T> $loaded
     * @param array{non-empty-string, Sort} $sort
     * @param ?positive-int $drop
     * @param ?positive-int $take
     */
    private function __construct(
        Repository $repository,
        Denormalize $denormalize,
        Instanciate $instanciate,
        ?Normalize $normalizeSpecification,
        Loaded $loaded,
        ?Specification $specification,
        ?array $sort,
        ?int $drop,
        ?int $take,
    ) {
        $this->repository = $repository;
        $this->denormalize = $denormalize;
        $this->instanciate = $instanciate;
        $this->normalizeSpecification = $normalizeSpecification;
        $this->loaded = $loaded;
        $this->specification = $specification;
        $this->sort = $sort;
        $this->drop = $drop;
        $this->take = $take;
    }

    /**
     * @template A of object
     *
     * @param Repository<A> $repository
     * @param Denormalize<A> $denormalize
     * @param Instanciate<A> $instanciate
     * @param Normalize<A> $normalizeSpecification
     * @param Loaded<A> $loaded
     *
     * @return self<A>
     */
    public static function of(
        Repository $repository,
        Denormalize $denormalize,
        Instanciate $instanciate,
        Normalize $normalizeSpecification,
        Loaded $loaded,
        Specification $specification,
    ): self {
        return new self(
            $repository,
            $denormalize,
            $instanciate,
            $normalizeSpecification,
            $loaded,
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
     * @param Denormalize<A> $denormalize
     * @param Instanciate<A> $instanciate
     * @param Loaded<A> $loaded
     *
     * @return self<A>
     */
    public static function all(
        Repository $repository,
        Denormalize $denormalize,
        Instanciate $instanciate,
        Loaded $loaded,
    ): self {
        return new self(
            $repository,
            $denormalize,
            $instanciate,
            null,
            $loaded,
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
            $this->denormalize,
            $this->instanciate,
            $this->normalizeSpecification,
            $this->loaded,
            $this->specification,
            $this->sort,
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
            $this->denormalize,
            $this->instanciate,
            $this->normalizeSpecification,
            $this->loaded,
            $this->specification,
            $this->sort,
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
    public function sort(string $property, Sort $direction): self
    {
        return new self(
            $this->repository,
            $this->denormalize,
            $this->instanciate,
            $this->normalizeSpecification,
            $this->loaded,
            $this->specification,
            [$property, $direction],
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
            ->repository
            ->fetch(
                $specification,
                $this->sort,
                $this->drop,
                $this->take,
            )
            ->map($denormalize)
            ->map(fn($denormalized) => $this->loaded->add($denormalized))
            ->map($this->instanciate);
    }
}
