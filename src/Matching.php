<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Definition\Aggregate,
    Adapter\Repository,
    Repository\Loaded,
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
    /** @var Normalize<T> */
    private Normalize $normalizeSpecification;
    /** @var Loaded<T> */
    private Loaded $loaded;
    /** @var Aggregate<T> */
    private Aggregate $definition;
    private Specification $specification;
    /** @var ?array{non-empty-string, Sort} */
    private ?array $sort;
    /** @var ?positive-int */
    private ?int $drop;
    /** @var ?positive-int */
    private ?int $take;

    /**
     * @param Repository<T> $repository
     * @param Normalize<T> $normalizeSpecification
     * @param Loaded<T> $loaded
     * @param Aggregate<T> $definition
     * @param array{non-empty-string, Sort} $sort
     * @param ?positive-int $drop
     * @param ?positive-int $take
     */
    private function __construct(
        Repository $repository,
        Normalize $normalizeSpecification,
        Loaded $loaded,
        Aggregate $definition,
        Specification $specification,
        ?array $sort,
        ?int $drop,
        ?int $take,
    ) {
        $this->repository = $repository;
        $this->normalizeSpecification = $normalizeSpecification;
        $this->loaded = $loaded;
        $this->definition = $definition;
        $this->specification = $specification;
        $this->sort = $sort;
        $this->drop = $drop;
        $this->take = $take;
    }

    /**
     * @template A of object
     *
     * @param Repository<A> $repository
     * @param Normalize<A> $normalizeSpecification
     * @param Loaded<A> $loaded
     * @param Aggregate<A> $definition
     *
     * @return self<A>
     */
    public static function of(
        Repository $repository,
        Normalize $normalizeSpecification,
        Loaded $loaded,
        Aggregate $definition,
        Specification $specification,
    ): self {
        return new self(
            $repository,
            $normalizeSpecification,
            $loaded,
            $definition,
            $specification,
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
            $this->normalizeSpecification,
            $this->loaded,
            $this->definition,
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
            $this->normalizeSpecification,
            $this->loaded,
            $this->definition,
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
            $this->normalizeSpecification,
            $this->loaded,
            $this->definition,
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
        /**
         * @psalm-suppress InvalidArgument For some reason Psalm lose track of the template after denormalization
         * @var Sequence<T>
         */
        return $this
            ->repository
            ->matching(
                ($this->normalizeSpecification)($this->specification),
                $this->sort,
                $this->drop,
                $this->take,
            )
            ->map($this->definition->denormalize(...))
            ->map($this->loaded->add(...));
    }
}
