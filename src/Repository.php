<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Definition\Aggregate,
    Repository\Loaded,
    Repository\Normalize,
    Repository\Denormalize,
    Repository\Instanciate,
    Repository\Extract,
    Repository\Diff,
    Repository\Sort,
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
    private Repository\Context $context;
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
    /** @var Sort<T> */
    private Sort $sort;

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
        Repository\Context $context,
    ) {
        $this->adapter = $adapter;
        $this->context = $context;
        $this->id = $definition->id();
        $this->inTransaction = $inTransaction;
        $this->normalizeSpecification = NormalizeSpecification::of($definition, $context);
        $this->loaded = Loaded::of($repositories, $definition);
        $this->normalize = Normalize::of($definition);
        $this->denormalize = Denormalize::of($definition);
        $this->instanciate = Instanciate::of($definition);
        $this->extract = Extract::of($definition);
        $this->diff = Diff::of($definition);
        $this->sort = Sort::of($definition);
    }

    /**
     * @internal
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
        Repository\Context $context,
    ): self {
        return new self(
            $repositories,
            $adapter,
            $definition,
            $inTransaction,
            $context,
        );
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
     * @param Id<T>|Specification $criteria
     */
    public function remove(Id|Specification $criteria): void
    {
        if (!($this->inTransaction)()) {
            throw new \LogicException('Mutation outside of a transaction');
        }

        if (!($criteria instanceof Specification)) {
            $this->adapter->remove(
                $this->id->normalize($criteria),
            );
            $this->loaded->remove($criteria);

            return;
        }

        $this->adapter->removeAll(
            ($this->normalizeSpecification)($criteria),
        );
    }

    /**
     * @return Matching<T>
     */
    public function matching(Specification $specification): Matching
    {
        return Matching::of(
            $this,
            $this->adapter,
            $this->id,
            $this->context,
            $this->denormalize,
            $this->instanciate,
            $this->normalizeSpecification,
            $this->loaded,
            $this->sort,
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
        return $this->adapter->any(match ($specification) {
            null => null,
            default => ($this->normalizeSpecification)($specification),
        });
    }

    public function none(Specification $specification = null): bool
    {
        return !$this->any($specification);
    }

    /**
     * @return Matching<T>
     */
    public function all(): Matching
    {
        return Matching::all(
            $this,
            $this->adapter,
            $this->id,
            $this->context,
            $this->denormalize,
            $this->instanciate,
            $this->loaded,
            $this->sort,
        );
    }
}
