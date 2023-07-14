<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Adapter,
    Definition\Aggregate,
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
    /** @var Aggregate<T> */
    private Aggregate $definition;
    /** @var \WeakMap<Id<T>, T> */
    private \WeakMap $loaded;

    /**
     * @param Adapter\Repository<T> $adapter
     * @param Aggregate<T> $definition
     */
    private function __construct(
        Adapter\Repository $adapter,
        Aggregate $definition,
    ) {
        $this->adapter = $adapter;
        $this->definition = $definition;
        /** @var \WeakMap<Id<T>, T> */
        $this->loaded = new \WeakMap;
    }

    /**
     * @template A of object
     *
     * @param Adapter\Repository<A> $adapter
     * @param Aggregate<A> $definition
     *
     * @return self<A>
     */
    public static function of(
        Adapter\Repository $adapter,
        Aggregate $definition,
    ): self {
        return new self($adapter, $definition);
    }

    /**
     * @param Id<T> $id
     *
     * @return Maybe<T>
     */
    public function get(Id $id): Maybe
    {
        return Maybe::of($this->loaded[$id] ?? null)->otherwise(
            fn() => $this
                ->adapter
                ->get($this->definition->id()->normalize($id))
                ->map(fn($raw) => $this->definition->denormalize($raw, $id))
                ->map(function($aggregate) use ($id) {
                    /** @var T $aggregate */
                    $this->loaded[$id] = $aggregate;

                    return $aggregate;
                }),
        );
    }

    /**
     * @param Id<T> $id
     */
    public function contains(Id $id): bool
    {
        return $this->adapter->contains(
            $this->definition->id()->normalize($id),
        );
    }

    /**
     * @param T $aggregate
     */
    public function put(object $aggregate): void
    {
        /** @var Id<T> */
        $id = $this->definition->id()->extract($aggregate);
        $this->loaded[$id] = $aggregate;

        match ($loaded = ($this->loaded[$id] ?? null)) {
            null => $this->adapter->add(
                $this->definition->normalize($aggregate),
            ),
            default => $this->adapter->update(
                $this->definition->normalize($aggregate), // TODO compute diff
            ),
        };
    }

    /**
     * @param Id<T> $id
     */
    public function delete(Id $id): void
    {
        $this->adapter->delete(
            $this->definition->id()->normalize($id),
        );
        $this->loaded->offsetUnset($id);
    }

    /**
     * @return Matching<T>
     */
    public function matching(Specification $specification): Matching
    {
        return Matching::of($this->definition->class(), $specification);
    }

    /**
     * @return 0|positive-int
     */
    public function size(?Specification $specification): int
    {
        return 0;
    }

    /**
     * @return Sequence<T>
     */
    public function all(): Sequence
    {
        return $this
            ->adapter
            ->all()
            ->map(fn($raw) => $this->definition->denormalize($raw))
            ->map(function($aggregate) {
                /**
                 * @var T $aggregate
                 * @var Id<T> $id
                 */
                $id = $this->definition->id()->extract($aggregate);
                $this->loaded[$id] = $aggregate;

                return $aggregate;
            });
    }
}
