<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
};
use Innmind\Filesystem\File;
use Innmind\Json\Json;
use Innmind\Immutable\{
    Maybe,
    Set,
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
     * @return callable(File): Maybe<Aggregate>
     */
    public function __invoke(Aggregate\Id $id = null): callable
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $id = match ($id) {
            null => fn(File $file) => Aggregate\Id::of(
                $this->definition->id()->property(),
                $file->name()->toString(),
            ),
            default => static fn(File $file) => $id,
        };

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress MixedArrayAccess
         */
        return static fn(File $file) => Maybe::just($file->content()->toString())
            ->map(Json::decode(...))
            ->filter(\is_array(...))
            ->map(static fn($raw) => Aggregate::of(
                $id($file),
                Set::of(...$raw['properties'])->map(static fn($property) => Aggregate\Property::of(
                    $property[0],
                    $property[1],
                )),
                Set::of(...$raw['entities'])->map(static fn($entity) => Aggregate\Entity::of(
                    $entity[0],
                    Set::of(...$entity[1])->map(static fn($property) => Aggregate\Property::of(
                        $property[0],
                        $property[1],
                    )),
                )),
                Set::of(...$raw['optionals'])->map(static fn($optional) => Aggregate\Optional::of(
                    $optional[0],
                    Maybe::of($optional[1])->map(static fn($properties) => Set::of(...$properties)->map(
                        static fn($property) => Aggregate\Property::of(
                            $property[0],
                            $property[1],
                        ),
                    )),
                )),
                Set::of(...$raw['collections'])->map(static fn($collection) => Aggregate\Collection::of(
                    $collection[0],
                    Set::of(...$collection[1])->map(static fn($properties) => Set::of(...$properties)->map(
                        static fn($property) => Aggregate\Property::of(
                            $property[0],
                            $property[1],
                        ),
                    )),
                )),
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
