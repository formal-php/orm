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
    Maybe,
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
     * @return class-string<T>
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    /**
     * @return Set<Property<T, mixed>>
     */
    public function properties(): Set
    {
        return $this->properties;
    }

    /**
     * The diff relies on the immutable nature of aggregates and the properties
     * being strictly typed
     *
     * This allows to not unwrap monadic types and accidently loading
     * unnecessary data
     *
     * @param T $then
     * @param T $now
     *
     * @return Maybe<Raw\Aggregate\Entity>
     */
    public function diff(object $then, object $now): Maybe
    {
        $thenValue = (new Extract)($then, Set::of($this->property))
            ->flatMap(fn($properties) => $properties->get($this->property));
        $nowValue = (new Extract)($now, Set::of($this->property))
            ->flatMap(fn($properties) => $properties->get($this->property));

        /** @psalm-suppress MixedArgument No way to tell psalm the property type */
        return Maybe::all($thenValue, $nowValue)
            ->flatMap(
                static fn(mixed $then, mixed $now) => Maybe::just($now)
                    ->filter(static fn($now) => $now !== $then)
                    ->map(
                        fn($now) => $this->properties->flatMap(
                            static fn($property) => $property
                                ->diff($then, $now)
                                ->toSequence()
                                ->toSet(),
                        ),
                    ),
            )
            ->filter(static fn($diff) => !$diff->empty())
            ->map(fn($properties) => Raw\Aggregate\Entity::of(
                $this->property,
                $properties,
            ));
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
