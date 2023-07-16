<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Formal\ORM\{
    Adapter,
    Definition\Aggregate,
    Repository\Loaded,
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
    /** @var Loaded<T> */
    private Loaded $loaded;

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
        $this->loaded = Loaded::of($definition);
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
        return $this
            ->loaded
            ->get($id)
            ->otherwise(
                fn() => $this
                    ->adapter
                    ->get($this->definition->id()->normalize($id))
                    ->map(fn($raw) => $this->definition->denormalize($raw, $id))
                    ->map($this->loaded->put($id)),
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
        $id = $this->definition->id()->extract($aggregate);
        $loaded = $this->loaded->get($id);

        $this->loaded->put($id)($aggregate);

        $_ = $loaded->match(
            fn($loaded) =>$this->adapter->update(
                $this->definition->normalize($aggregate), // TODO compute diff
            ),
            fn() => $this->adapter->add(
                $this->definition->normalize($aggregate),
            ),
        );
    }

    /**
     * @param Id<T> $id
     */
    public function delete(Id $id): void
    {
        $this->adapter->delete(
            $this->definition->id()->normalize($id),
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
            $this->loaded,
            $this->definition,
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
            default => $this->definition->normalizeSpecification($specification),
        });
    }

    /**
     * @return Sequence<T>
     */
    public function all(): Sequence
    {
        /**
         * @psalm-suppress InvalidArgument For some reason Psalm lose track of the template after denormalization
         * @var Sequence<T>
         */
        return $this
            ->adapter
            ->all()
            ->map(fn($raw) => $this->definition->denormalize($raw))
            ->map($this->loaded->add(...));
    }
}
