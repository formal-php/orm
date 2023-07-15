<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Formal\ORM\{
    Id,
    Raw,
};
use Innmind\Reflection\{
    ReflectionClass,
    Instanciate,
};
use Innmind\Immutable\{
    Str,
    Set,
    Map,
    Monoid\Concat,
    Predicate\Instance,
};

/**
 * @template T of object
 */
final class Aggregate
{
    /** @var class-string<T> */
    private string $class;
    private Aggregate\Id $id;
    /** @var Set<Aggregate\Property> */
    private Set $properties;

    /**
     * @param class-string<T> $class
     * @param Set<Aggregate\Property> $properties
     */
    private function __construct(
        string $class,
        Aggregate\Id $id,
        Set $properties,
    ) {
        $this->class = $class;
        $this->id = $id;
        $this->properties = $properties;
    }

    /**
     * @template A
     *
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function of(Types $types, string $class): self
    {
        $properties = ReflectionClass::of($class)->properties();
        $id = $properties
            ->filter(static fn($property) => $property->type()->toString() === Id::class)
            ->filter(
                static fn($property) => $property
                    ->attributes()
                    ->filter(static fn($attribute) => $attribute->class() === Template::class)
                    ->map(static fn($attribute) => $attribute->instance())
                    ->keep(Instance::of(Template::class))
                    ->any(static fn($template) => $template->is($class)),
            )
            ->find(static fn() => true) // TODO mention in the doc that only one property can reference an id of the current aggregate
            ->match(
                static fn($property) => Aggregate\Id::of($property->name(), $class),
                static fn() => throw new \LogicException('One property must be typed Id<self>'),
            );
        /** @psalm-suppress ArgumentTypeCoercion TODO fix in innmind/reflection */
        $props = $properties
            ->exclude(static fn($property) => $property->name() === $id->property())
            ->flatMap(static fn($property) => $types($property->type()->toString())
                ->map(static fn($type) => Aggregate\Property::of(
                    $class,
                    $property->name(),
                    $type,
                ))
                ->toSequence()
                ->toSet(),
            );

        return new self($class, $id, $props);
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
    public function name(): string
    {
        /** @var non-empty-string */
        return Str::of($this->class)
            ->split('\\')
            ->takeEnd(1)
            ->fold(new Concat)
            ->toLower()
            ->toString();
    }

    public function id(): Aggregate\Id
    {
        return $this->id;
    }

    /**
     * @return Set<Aggregate\Property>
     */
    public function properties(): Set
    {
        return $this->properties;
    }

    /**
     * @param T $aggregate
     */
    public function normalize(object $aggregate): Raw\Aggregate
    {
        /** @var Id<T> */
        $id = $this->id()->extract($aggregate);

        return Raw\Aggregate::of(
            $this->id()->normalize($id),
            $this
                ->properties
                ->map(static fn($property) => $property->normalize($aggregate)),
        );
    }

    /**
     * @param Id<T> $id
     *
     * @return T
     */
    public function denormalize(Raw\Aggregate $data, Id $id = null): object
    {
        $id = match ($id) {
            null => $this->id()->denormalize($data->id()),
            default => $id,
        };

        $properties = Map::of(
            [$this->id()->property(), $id],
            ...$data
                ->properties()
                ->flatMap(
                    fn($property) => $this
                        ->properties
                        ->find(static fn($definition) => $definition->name() === $property->name())
                        ->map(static fn($definition): mixed => $definition->denormalize($property->value()))
                        ->map(static fn($value) => [$property->name(), $value])
                        ->toSequence()
                        ->toSet(),
                )
                ->toList(),
        );

        /** @var T */
        return (new Instanciate)($this->class, $properties)->match(
            static fn($aggregate) => $aggregate,
            fn() => throw new \RuntimeException("Unable to denormalize aggregate of type {$this->class}"),
        );
    }
}
