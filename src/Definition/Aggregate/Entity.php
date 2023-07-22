<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Formal\ORM\{
    Definition\Aggregate\Entity\Kind,
    Raw,
};
use Innmind\Reflection\{
    Extract,
    Instanciate,
};
use Innmind\Immutable\{
    Set,
    Map,
    Predicate\Instance,
};

/**
 * @template T of object
 */
final class Entity
{
    /** @var class-string<T> */
    private string $class;
    /** @var non-empty-string */
    private string $property;
    private Kind $kind;
    /** @var Set<Property<T, mixed>> */
    private Set $properties;

    /**
     * @param class-string<T> $class
     * @param non-empty-string $property
     * @param Set<Property<T, mixed>> $properties
     */
    private function __construct(
        string $class,
        string $property,
        Kind $kind,
        Set $properties,
    ) {
        $this->class = $class;
        $this->property = $property;
        $this->kind = $kind;
        $this->properties = $properties;
    }

    /**
     * @template A of object
     *
     * @param class-string<A> $class
     * @param non-empty-string $property
     * @param Set<Property<A, mixed>> $properties
     *
     * @return self<A>
     */
    public static function required(
        string $class,
        string $property,
        Set $properties,
    ): self {
        return new self($class, $property, Kind::required, $properties);
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    public function normalize(object $aggregate): Raw\Aggregate\Entity
    {
        /** @var T */
        $entity = (new Extract)($aggregate, Set::of($this->property))
            ->flatMap(fn($properties) => $properties->get($this->property))
            ->keep(Instance::of($this->class))
            ->match(
                static fn($entity) => $entity,
                fn() => throw new \LogicException("Unable to extract {$this->class}@{$this->property}"),
            );

        return Raw\Aggregate\Entity::of(
            $this->property,
            $this->properties->map(
                static fn($property) => $property->normalize($entity),
            ),
        );
    }

    /**
     * @return T
     */
    public function denormalize(Raw\Aggregate\Entity $data): object
    {
        $properties = $this
            ->properties
            ->flatMap(
                static fn($property) => $data
                    ->property($property->name())
                    ->map(static fn($raw): mixed => [
                        $property->name(),
                        $property->denormalize($raw->value()),
                    ])
                    ->toSequence()
                    ->toSet(),
            )
            ->toList();

        /** @var T */
        return (new Instanciate)($this->class, Map::of(...$properties))->match(
            static fn($entity) => $entity,
            fn() => throw new \RuntimeException("Unable to denormalize entity of type {$this->class}"),
        );
    }
}
