<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Adapter\Repository as RepositoryInterface,
    Definition\Aggregate as Definition,
    Raw\Aggregate,
    Raw\Diff,
    Sort,
};
use Innmind\Filesystem\{
    Adapter as Storage,
    Name,
    Directory,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    Predicate\Instance,
};

/**
 * @internal
 * @template T of object
 * @implements RepositoryInterface<T>
 */
final class Repository implements RepositoryInterface
{
    private Transaction $transaction;
    /** @var Definition<T> */
    private Definition $definition;
    /** @var Fold<T> */
    private Fold $fold;
    private Encode $encode;
    /** @var Decode<T> */
    private Decode $decode;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Transaction $transaction, Definition $definition)
    {
        $this->transaction = $transaction;
        $this->definition = $definition;
        $this->fold = Fold::of($definition);
        $this->encode = Encode::new();
        $this->decode = Decode::of($definition);
    }

    /**
     * @internal
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(Transaction $transaction, Definition $definition): self
    {
        return new self($transaction, $definition);
    }

    public function get(Aggregate\Id $id): Maybe
    {
        return $this
            ->directory()
            ->get(Name::of($id->value()))
            ->flatMap(($this->decode)($id));
    }

    public function contains(Aggregate\Id $id): bool
    {
        return $this
            ->directory()
            ->contains(Name::of($id->value()));
    }

    public function add(Aggregate $data): void
    {
        $this->transaction->mutate(
            fn($adapter) => $adapter->add(
                Directory\Directory::named($this->definition->name())->add(
                    ($this->encode)($data),
                ),
            ),
        );
    }

    public function update(Diff $data): void
    {
        $_ = $this
            ->get($data->id())
            ->map(Apply::of($data))
            ->match(
                $this->add(...),
                static fn() => null,
            );
    }

    public function remove(Aggregate\Id $id): void
    {
        $this->transaction->mutate(
            fn($adapter) => $adapter->add(
                Directory\Directory::named($this->definition->name())->remove(
                    Name::of($id->value()),
                ),
            ),
        );
    }

    public function fetch(
        ?Specification $specification,
        null|Sort\Property|Sort\Entity $sort,
        ?int $drop,
        ?int $take,
    ): Sequence {
        $aggregates = $this->all();

        if ($specification) {
            $aggregates = $aggregates->filter(
                ($this->fold)($specification),
            );
        }

        if ($sort) {
            $compare = match ($sort->direction()) {
                Sort::asc => static fn(null|string|int|bool $a, null|string|int|bool $b) => $a <=> $b,
                Sort::desc => static fn(null|string|int|bool $a, null|string|int|bool $b) => $b <=> $a,
            };
            $pluck = match (true) {
                $sort instanceof Sort\Property => static fn(Aggregate $x): mixed => $x
                    ->properties()
                    ->find(static fn($property) => $property->name() === $sort->name())
                    ->match(
                        static fn($property) => $property->value(),
                        static fn() => throw new \LogicException("'{$sort->name()}' not found"),
                    ),
                $sort instanceof Sort\Entity => static fn(Aggregate $x): mixed => $x
                    ->entities()
                    ->find(static fn($entity) => $entity->name() === $sort->name())
                    ->flatMap(
                        static fn($entity) => $entity
                            ->properties()
                            ->find(static fn($property) => $property->name() === $sort->property()->name()),
                    )
                    ->match(
                        static fn($property) => $property->value(),
                        static fn() => throw new \LogicException("'{$sort->name()}.{$sort->property()->name()}' not found"),
                    )
            };

            $aggregates = $aggregates->sort(static fn($a, $b) => $compare(
                $pluck($a),
                $pluck($b),
            ));
        }

        if (\is_int($drop)) {
            $aggregates = $aggregates->drop($drop);
        }

        if (\is_int($take)) {
            $aggregates = $aggregates->take($take);
        }

        return $aggregates;
    }

    public function size(Specification $specification = null): int
    {
        $filter = match ($specification) {
            null => static fn(Aggregate $aggregate) => true,
            default => ($this->fold)($specification),
        };

        return $this
            ->all()
            ->filter($filter)
            ->size();
    }

    /**
     * @return Sequence<Aggregate>
     */
    private function all(): Sequence
    {
        $decode = ($this->decode)();

        return $this
            ->directory()
            ->files()
            ->flatMap(static fn($file) => $decode($file)->toSequence());
    }

    private function directory(): Directory
    {
        $name = Name::of($this->definition->name());

        return $this->transaction->get($name);
    }
}
