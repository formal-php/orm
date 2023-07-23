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
    private Storage $adapter;
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
    private function __construct(Storage $adapter, Definition $definition)
    {
        $this->adapter = $adapter;
        $this->definition = $definition;
        $this->fold = Fold::of($definition);
        $this->encode = Encode::new();
        $this->decode = Decode::of($definition);
    }

    /**
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(Storage $adapter, Definition $definition): self
    {
        return new self($adapter, $definition);
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
        $this->adapter->add(
            Directory\Directory::named($this->definition->name())->add(
                ($this->encode)($data),
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

    public function delete(Aggregate\Id $id): void
    {
        $this->adapter->add(
            $this->directory()->remove(Name::of($id->value())),
        );
    }

    public function matching(
        Specification $specification,
        ?array $sort,
        ?int $drop,
        ?int $take,
    ): Sequence {
        $filter = ($this->fold)($specification);
        $aggregates = $this
            ->all()
            ->filter($filter);

        if (\is_array($sort)) {
            [$property, $direction] = $sort;
            $compare = match ($direction) {
                Sort::asc => static fn(null|string|int|bool $a, null|string|int|bool $b) => $a <=> $b,
                Sort::desc => static fn(null|string|int|bool $a, null|string|int|bool $b) => $b <=> $a,
            };

            $aggregates = $aggregates->sort(static fn($a, $b) => $compare(
                $a
                    ->property($property)
                    ->match(
                        static fn($property) => $property->value(),
                        static fn() => throw new \LogicException("'$property' not found"),
                    ),
                $b
                    ->property($property)
                    ->match(
                        static fn($property) => $property->value(),
                        static fn() => throw new \LogicException("'$property' not found"),
                    ),
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

    public function all(): Sequence
    {
        return $this
            ->directory()
            ->files()
            ->flatMap(fn($file) => ($this->decode)()($file)->toSequence());
    }

    private function directory(): Directory
    {
        $name = Name::of($this->definition->name());

        return $this
            ->adapter
            ->get($name)
            ->keep(Instance::of(Directory::class))
            ->match(
                static fn($directory) => $directory,
                static fn() => Directory\Directory::of($name),
            );
    }
}
