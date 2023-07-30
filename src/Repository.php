<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Adapter,
    Definition\Aggregate,
    Repository\Loaded,
    Repository\Normalize,
    Repository\Denormalize,
    Repository\Diff,
    Specification\Normalize as NormalizeSpecification,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    Predicate\Instance,
};

/**
 * @template T of object
 */
final class Repository
{
    /** @var Adapter\Repository<T> */
    private Adapter\Repository $adapter;
    /** @var Aggregate\Identity<T> */
    private Aggregate\Identity $id;
    /** @var \Closure(): bool */
    private \Closure $inTransaction;
    /** @var NormalizeSpecification<T> */
    private NormalizeSpecification $normalizeSpecification;
    /** @var Loaded<T> */
    private Loaded $loaded;
    /** @var Normalize<T> */
    private Normalize $normalize;
    /** @var Denormalize<T> */
    private Denormalize $denormalize;
    /** @var Diff<T> */
    private Diff $diff;

    /**
     * @param Adapter\Repository<T> $adapter
     * @param Aggregate<T> $definition
     * @param \Closure(): bool $inTransaction
     */
    private function __construct(
        Adapter\Repository $adapter,
        Aggregate $definition,
        \Closure $inTransaction,
    ) {
        $this->adapter = $adapter;
        $this->id = $definition->id();
        $this->inTransaction = $inTransaction;
        $this->normalizeSpecification = NormalizeSpecification::of($definition);
        $this->loaded = Loaded::of($definition);
        $this->normalize = Normalize::of($definition);
        $this->denormalize = Denormalize::of($definition);
        $this->diff = Diff::of($definition);
    }

    /**
     * @template A of object
     *
     * @param Adapter\Repository<A> $adapter
     * @param Aggregate<A> $definition
     * @param \Closure(): bool $inTransaction
     *
     * @return self<A>
     */
    public static function of(
        Adapter\Repository $adapter,
        Aggregate $definition,
        \Closure $inTransaction,
    ): self {
        return new self($adapter, $definition, $inTransaction);
    }

    /**
     * @param Id<T> $id
     *
     * @return Maybe<T>
     */
    public function get(Id $id): Maybe
    {
        return $this
            ->loaded
            ->get($id)
            ->otherwise(
                fn() => $this
                    ->adapter
                    ->get($this->id->normalize($id))
                    ->map(($this->denormalize)($id))
                    ->map($this->loaded->put($id)),
            );
    }

    /**
     * @param Id<T> $id
     */
    public function contains(Id $id): bool
    {
        return $this->adapter->contains(
            $this->id->normalize($id),
        );
    }

    /**
     * @param T $aggregate
     */
    public function put(object $aggregate): void
    {
        if (!($this->inTransaction)()) {
            throw new \LogicException('Mutation outside of a transaction');
        }

        $id = $this->id->extract($aggregate);
        $loaded = $this->loaded->get($id);

        $this->loaded->put($id)($aggregate);

        /** @psalm-suppress InvalidArgument For some reason Psalm lose track of $loaded type */
        $_ = $loaded->match(
            fn($loaded) =>$this->adapter->update(
                ($this->diff)($loaded, $aggregate),
            ),
            fn() => $this->adapter->add(
                ($this->normalize)($aggregate),
            ),
        );
    }

    /**
     * @param Id<T> $id
     */
    public function delete(Id $id): void
    {
        if (!($this->inTransaction)()) {
            throw new \LogicException('Mutation outside of a transaction');
        }

        $this->adapter->delete(
            $this->id->normalize($id),
        );
        $this->loaded->remove($id);
    }

    /**
     * @return Matching<T>
     */
    public function matching(Specification $specification): Matching
    {
        return Matching::of(
            $this->adapter,
            $this->denormalize,
            $this->normalizeSpecification,
            $this->loaded,
            $specification,
        );
    }

    /**
     * @return 0|positive-int
     */
    public function size(Specification $specification = null): int
    {
        return $this->adapter->size(match ($specification) {
            null => null,
            default => ($this->normalizeSpecification)($specification),
        });
    }

    /**
     * @return Sequence<T>
     */
    public function all(): Sequence
    {
        $denormalize = ($this->denormalize)();

        /**
         * @psalm-suppress InvalidArgument For some reason Psalm lose track of the template after denormalization
         * @var Sequence<T>
         */
        return $this
            ->adapter
            ->all()
            ->map($denormalize)
            ->map($this->loaded->add(...));
    }
}
