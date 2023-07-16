<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Adapter\Repository as RepositoryInterface,
    Definition\Aggregate as Definition,
    Raw\Aggregate,
};
use Innmind\Filesystem\{
    Adapter as Storage,
    Name,
    Directory,
    File\File,
    File\Content,
};
use Innmind\Specification\Specification;
use Innmind\Json\Json;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    Set,
    Predicate\Instance,
};

/**
 * @template T of object
 * @implements RepositoryInterface<T>
 */
final class Repository implements RepositoryInterface
{
    private Storage $adapter;
    /** @var Definition<T> */
    private Definition $definition;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Storage $adapter, Definition $definition)
    {
        $this->adapter = $adapter;
        $this->definition = $definition;
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
        /**
         * @psalm-suppress NamedArgumentNotAllowed
         * @psalm-suppress MixedArgument
         * @psalm-suppress MixedArrayAccess
         */
        return $this
            ->directory()
            ->get(Name::of($id->value()))
            ->map(static fn($file) => $file->content()->toString())
            ->map(Json::decode(...))
            ->filter(\is_array(...))
            ->map(static fn(array $raw) => Aggregate::of(
                $id,
                Set::of(...$raw)->map(static fn($property) => Aggregate\Property::of(
                    $property[0],
                    $property[1],
                )),
            ));
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
                File::named(
                    $data->id()->value(),
                    Content\Lines::ofContent(Json::encode([
                        ...$data
                            ->properties()
                            ->map(static fn($property) => [$property->name(), $property->value()])
                            ->toList(),
                    ])),
                ),
            ),
        );
    }

    public function update(Aggregate $data): void
    {
        $this->add($data);
    }

    public function delete(Aggregate\Id $id): void
    {
        $this->adapter->add(
            $this->directory()->remove(Name::of($id->value())),
        );
    }

    public function size(Specification $specification = null): int
    {
        return $this
            ->directory()
            ->files()
            ->size();
    }

    public function all(): Sequence
    {
        /**
         * @psalm-suppress ArgumentTypeCoercion
         * @psalm-suppress NamedArgumentNotAllowed
         * @psalm-suppress MixedArgument
         * @psalm-suppress MixedArrayAccess
         */
        return $this
            ->directory()
            ->files()
            ->flatMap(
                fn($file) => Maybe::just($file->content()->toString())
                    ->map(Json::decode(...))
                    ->filter(\is_array(...))
                    ->map(static fn(array $raw) => Set::of(...$raw)->map(static fn($property) => Aggregate\Property::of(
                        $property[0],
                        $property[1],
                    )))
                    ->map(fn($properties) => Aggregate::of(
                        Aggregate\Id::of(
                            $this->definition->id()->property(),
                            $file->name()->toString(),
                        ),
                        $properties,
                    ))
                    ->toSequence(),
            );
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
