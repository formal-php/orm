<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
};
use Innmind\Filesystem\{
    File,
    Directory,
    Name,
};
use Innmind\Json\Json;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    Predicate\Instance,
};

/**
 * @internal
 * @template T of object
 */
final class Decode
{
    /** @var Definition<T> */
    private Definition $definition;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * @return callable(Directory): Maybe<Aggregate>
     */
    public function __invoke(Aggregate\Id $id = null): callable
    {
        $property = $this->definition->id()->property();
        /** @psalm-suppress ArgumentTypeCoercion */
        $id = match ($id) {
            null => static fn(Directory $directory) => Aggregate\Id::of(
                $property,
                $directory->name()->toString(),
            ),
            default => static fn(Directory $directory) => $id,
        };

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress MixedArrayAccess
         * @psalm-suppress MixedArgumentTypeCoercion
         */
        return static fn(Directory $directory) => Maybe::all(
            $directory
                ->get(Name::of('properties'))
                ->keep(Instance::of(Directory::class))
                ->memoize()
                ->map(
                    static fn($properties) => $properties
                        ->all()
                        ->keep(Instance::of(File::class))
                        ->memoize()
                        ->map(static fn($file) => Aggregate\Property::of(
                            $file->name()->toString(),
                            Json::decode($file->content()->toString()),
                        )),
                ),
            $directory
                ->get(Name::of('entities'))
                ->keep(Instance::of(Directory::class))
                ->memoize()
                ->map(
                    static fn($entities) => $entities
                        ->all()
                        ->keep(Instance::of(Directory::class))
                        ->memoize()
                        ->map(
                            static fn($entity) => Aggregate\Entity::of(
                                $entity->name()->toString(),
                                $entity
                                    ->all()
                                    ->keep(Instance::of(File::class))
                                    ->memoize()
                                    ->map(static fn($property) => Aggregate\Property::of(
                                        $property->name()->toString(),
                                        Json::decode($property->content()->toString()),
                                    )),
                            ),
                        ),
                ),
            $directory
                ->get(Name::of('optionals'))
                ->keep(Instance::of(Directory::class))
                ->memoize()
                ->map(
                    static fn($optionals) => $optionals
                        ->all()
                        ->keep(Instance::of(Directory::class))
                        ->memoize()
                        ->map(
                            static fn($optional) => Aggregate\Optional::of(
                                $optional->name()->toString(),
                                $optional
                                    ->get(Name::of('just'))
                                    ->keep(Instance::of(Directory::class))
                                    ->memoize()
                                    ->map(
                                        static fn($just) => $just
                                            ->all()
                                            ->keep(Instance::of(File::class))
                                            ->memoize()
                                            ->map(static fn($property) => Aggregate\Property::of(
                                                $property->name()->toString(),
                                                Json::decode($property->content()->toString()),
                                            )),
                                    ),
                            ),
                        ),
                ),
            $directory
                ->get(Name::of('collections'))
                ->keep(Instance::of(Directory::class))
                ->memoize()
                ->map(
                    static fn($collections) => $collections
                        ->all()
                        ->keep(Instance::of(File::class))
                        ->memoize()
                        ->map(
                            static fn($collection) => Aggregate\Collection::of(
                                $collection->name()->toString(),
                                Sequence::of(...Json::decode($collection->content()->toString()))
                                    ->map(
                                        static fn($entity) => Aggregate\Collection\Entity::of(
                                            Sequence::of(...$entity)->map(
                                                static fn($property) => Aggregate\Property::of(
                                                    $property[0],
                                                    $property[1],
                                                ),
                                            ),
                                        ),
                                    )
                                    ->toSet(),
                            ),
                        ),
                ),
        )->map(static fn(Sequence $properties, Sequence $entities, Sequence $optionals, Sequence $collections) => Aggregate::of(
            $id($directory),
            $properties,
            $entities,
            $optionals,
            $collections,
        ));
    }

    /**
     * @internal
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(Definition $definition): self
    {
        return new self($definition);
    }
}
