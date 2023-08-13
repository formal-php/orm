<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Adapter,
    Definition\Aggregate,
    Repository\Loaded,
    Repository\Normalize,
    Repository\Denormalize,
    Repository\Instanciate,
    Repository\Extract,
    Repository\Diff,
    Specification\Normalize as NormalizeSpecification,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\Maybe;

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
    /** @var Instanciate<T> */
    private Instanciate $instanciate;
    /** @var Extract<T> */
    private Extract $extract;
    /** @var Diff<T> */
    private Diff $diff;

    /**
     * @param Adapter\Repository<T> $adapter
     * @param Aggregate<T> $definition
     * @param \Closure(): bool $inTransaction
     */
    private function __construct(
        Repository\Active $repositories,
        Adapter\Repository $adapter,
        Aggregate $definition,
        \Closure $inTransaction,
    ) {
        $this->adapter = $adapter;
        $this->id = $definition->id();
        $this->inTransaction = $inTransaction;
        $this->normalizeSpecification = NormalizeSpecification::of($definition);
        $this->loaded = Loaded::of($repositories, $definition);
        $this->normalize = Normalize::of($definition);
        $this->denormalize = Denormalize::of($definition);
        $this->instanciate = Instanciate::of($definition);
        $this->extract = Extract::of($definition);
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
        Repository\Active $repositories,
        Adapter\Repository $adapter,
        Aggregate $definition,
        \Closure $inTransaction,
    ): self {
        return new self($repositories, $adapter, $definition, $inTransaction);
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
            ->map($this->instanciate)
            ->otherwise(
                fn() => $this
                    ->adapter
                    ->get($this->id->normalize($id))
                    ->map(($this->denormalize)($id))
                    ->map(fn($denormalized) => $this->loaded->add($this, $denormalized))
                    ->map($this->instanciate),
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

        $now = ($this->extract)($aggregate);
        $then = $this->loaded->get($now->id());

        $this->loaded->add($this, $now);

        /** @psalm-suppress InvalidArgument For some reason Psalm lose track of $then type */
        $_ = $then->match(
            fn($then) => $this->adapter->update(
                ($this->diff)($then, $now),
            ),
            fn() => $this->adapter->add(
                ($this->normalize)($now),
            ),
        );
    }

    /**
     * @param Id<T> $id
     */
    public function remove(Id $id): void
    {
        if (!($this->inTransaction)()) {
            throw new \LogicException('Mutation outside of a transaction');
        }

        $this->adapter->remove(
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
            $this,
            $this->adapter,
            $this->denormalize,
            $this->instanciate,
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

    public function any(Specification $specification = null): bool
    {
        return $this->size($specification) !== 0;
    }

    public function none(Specification $specification = null): bool
    {
        return $this->size($specification) === 0;
    }

    /**
     * @return Matching<T>
     */
    public function all(): Matching
    {
        return Matching::all(
            $this,
            $this->adapter,
            $this->denormalize,
            $this->instanciate,
            $this->loaded,
        );
    }
}
