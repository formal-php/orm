<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Formal\ORM\{
    Id,
    Definition\Template,
    Definition\Types,
};
use Innmind\Reflection\ReflectionProperty;
use Innmind\Immutable\{
    Maybe,
    Set,
    Predicate\Instance,
};

/**
 * @template T of object
 * @internal
 */
final class Parsing
{
    /** @var class-string<T> */
    private string $class;
    /** @var Maybe<Identity<T>> */
    private Maybe $id;
    /** @var Set<Property<T, mixed>> */
    private Set $properties;

    /**
     * @param class-string<T> $class
     * @param Maybe<Identity<T>> $id
     * @param Set<Property<T, mixed>> $properties
     */
    private function __construct(
        string $class,
        Maybe $id,
        Set $properties,
    ) {
        $this->class = $class;
        $this->id = $id;
        $this->properties = $properties;
    }

    /**
     * @template A of object
     *
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function of(string $class): self
    {
        /** @var Maybe<Identity<A>> */
        $id = Maybe::nothing();

        return new self($class, $id, Set::of());
    }

    /**
     * @param ReflectionProperty<T> $property
     *
     * @return self<T>
     */
    public function with(ReflectionProperty $property, Types $types): self
    {
        return $this
            ->parse($property, $types)
            ->match(
                fn($parsed) => match ($parsed::class) {
                    Identity::class => new self(
                        $this->class,
                        Maybe::just($parsed), // we can override here because we force the id property to be named "id" so there can only be one
                        $this->properties,
                    ),
                    Property::class => new self(
                        $this->class,
                        $this->id,
                        ($this->properties)($parsed),
                    ),
                },
                fn() => $this, // silently discard unparseable properties
            );
    }

    /**
     * @return Maybe<Identity<T>>
     */
    public function id(): Maybe
    {
        return $this->id;
    }

    /**
     * @return Set<Property<T, mixed>>
     */
    public function properties(): Set
    {
        return $this->properties;
    }

    /**
     * @param ReflectionProperty<T> $property
     *
     * @return Maybe<Identity<T>|Property<T, mixed>>
     */
    private function parse(ReflectionProperty $property, Types $types): Maybe
    {
        return $this
            ->parseId($property)
            ->otherwise(fn() => $this->parseProperty($property, $types));
    }

    /**
     * @param ReflectionProperty<T> $property
     *
     * @return Maybe<Identity<T>>
     */
    private function parseId(ReflectionProperty $property): Maybe
    {
        return Maybe::just($property)
            ->filter(static fn($property) => $property->name() === 'id')
            ->filter(static fn($property) => $property->type()->toString() === Id::class)
            ->filter(
                fn($property) => $property
                    ->attributes()
                    ->filter(static fn($attribute) => $attribute->class() === Template::class)
                    ->map(static fn($attribute) => $attribute->instance())
                    ->keep(Instance::of(Template::class))
                    ->any(fn($template) => $template->is($this->class)),
            )
            ->map(fn($property) => Identity::of($property->name(), $this->class));
    }

    /**
     * @param ReflectionProperty<T> $property
     *
     * @return Maybe<Property<T, mixed>>
     */
    private function parseProperty(ReflectionProperty $property, Types $types): Maybe
    {
        return Maybe::just($property)
            ->exclude(static fn($property) => $property->name() === 'id')
            ->flatMap(static fn($property) => $types(
                $property->type()->type(),
                $property
                    ->attributes()
                    ->find(static fn($attribute) => $attribute->class() === Template::class)
                    ->map(static fn($attribute) => $attribute->instance())
                    ->keep(Instance::of(Template::class))
                    ->match(
                        static fn($template) => $template,
                        static fn() => null,
                    ),
            ))
            ->map(fn($type) => Property::of(
                $this->class,
                $property->name(),
                $type,
            ));
    }
}
