<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Adapter\Repository as RepositoryInterface,
    Definition\Aggregate as Definition,
    Raw\Aggregate,
    Specification\Property as PropertySpecification,
    Sort,
};
use Innmind\Filesystem\{
    Adapter as Storage,
    Name,
    Directory,
    File\File,
    File\Content,
};
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Operator,
    Not,
    Sign,
};
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

    public function matching(
        Specification $specification,
        ?array $sort,
        ?int $drop,
        ?int $take,
    ): Sequence {
        $aggregates = $this
            ->all()
            ->filter(static fn($aggregate) => self::filter($aggregate, $specification));

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
        return $this
            ->all()
            ->filter(static fn($aggregate) => self::filter($aggregate, $specification))
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

    private static function filter(
        Aggregate $aggregate,
        Specification $specification = null,
    ): bool {
        if (\is_null($specification)) {
            return true;
        }

        if ($specification instanceof Not) {
            return !self::filter($aggregate, $specification->specification());
        }

        if ($specification instanceof Composite) {
            $left = self::filter($aggregate, $specification->left());
            $right = self::filter($aggregate, $specification->right());

            return match ($specification->operator()) {
                Operator::and => $left && $right,
                Operator::or => $left || $right,
            };
        }

        if (!($specification instanceof PropertySpecification)) {
            $class = $specification::class;

            throw new \LogicException("Unsupported specification '$class'");
        }

        if ($specification->property() === $aggregate->id()->name()) {
            return self::filterValue($aggregate->id()->value(), $specification);
        }

        return $aggregate
            ->property($specification->property())
            ->match(
                static fn($property) => self::filterValue($property->value(), $specification),
                static fn() => false,
            );
    }

    private static function filterValue(
        null|string|int|bool $value,
        Comparator $specification,
    ): bool {
        /** @psalm-suppress MixedArgument */
        return match ($specification->sign()) {
            Sign::equality => $value === $specification->value(),
            Sign::inequality => $value !== $specification->value(),
            Sign::lessThan => $value < $specification->value(),
            Sign::moreThan => $value > $specification->value(),
            Sign::lessThanOrEqual => $value <= $specification->value(),
            Sign::moreThanOrEqual => $value >= $specification->value(),
            Sign::isNull => \is_null($value),
            Sign::isNotNull => !\is_null($value),
            Sign::startsWith => \is_string($value) && \str_starts_with($value, $specification->value()),
            Sign::endsWith => \is_string($value) && \str_ends_with($value, $specification->value()),
            Sign::contains => \is_string($value) && \str_contains($value, $specification->value()),
            Sign::in => false, // not supported
        };
    }
}
