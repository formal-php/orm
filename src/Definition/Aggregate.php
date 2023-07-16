<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Formal\ORM\{
    Id,
    Raw,
    Specification\Property as PropertySpecification,
};
use Innmind\Reflection\{
    ReflectionClass,
    Instanciate,
};
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Not,
    Operator,
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
    /** @var Aggregate\Id<T> */
    private Aggregate\Id $id;
    /** @var Set<Aggregate\Property> */
    private Set $properties;

    /**
     * @param class-string<T> $class
     * @param Aggregate\Id<T> $id
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
            ->flatMap(static fn($property) => $types($property->type()->type())
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

    /**
     * @return Aggregate\Id<T>
     */
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

    public function normalizeSpecification(Specification $specification): Specification
    {
        if ($specification instanceof Not) {
            return $this
                ->normalizeSpecification($specification->specification())
                ->not();
        }

        if ($specification instanceof Composite) {
            $left = $this->normalizeSpecification($specification->left());
            $right = $this->normalizeSpecification($specification->right());

            return match ($specification->operator()) {
                Operator::and => $left->and($right),
                Operator::or => $left->or($right),
            };
        }

        if (!($specification instanceof Comparator)) {
            $class = $specification::class;

            throw new \LogicException("Unsupported specification '$class'");
        }

        $property = $specification->property();

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress MixedMethodCall
         */
        return PropertySpecification::of(
            $property,
            $specification->sign(),
            match ($property) {
                $this->id()->property() => $specification->value()->toString(),
                default => $this
                    ->properties
                    ->find(static fn($definition) => $definition->name() === $property)
                    ->map(static fn($property) => $property->type()->normalize($specification->value()))
                    ->match(
                        static fn($value) => $value,
                        static fn() => throw new \LogicException("Unknown property '$property'"),
                    ),
            },
        );
    }
}
