<?php
declare(strict_types = 1);

namespace Formal\ORM\Specification;

use Formal\ORM\{
    Definition\Aggregate,
    Id,
};
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Not,
    Operator,
};
use Innmind\Immutable\{
    Set,
    Sequence,
    Map,
    Str,
    Predicate\Instance,
};

/**
 * @internal
 * @template T of object
 */
final class Normalize
{
    /** @var Aggregate<T> */
    private Aggregate $definition;
    /** @var Map<non-empty-string, Aggregate\Property<T, mixed>> */
    private Map $properties;
    /** @var Map<non-empty-string, Map<non-empty-string, Aggregate\Property>> */
    private Map $entities;

    /**
     * @param Aggregate<T> $definition
     */
    private function __construct(Aggregate $definition)
    {
        $this->definition = $definition;
        $this->properties = Map::of(
            ...$definition
                ->properties()
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );
        $this->entities = Map::of(
            ...$definition
                ->entities()
                ->map(
                    static fn($entity) => [
                        $entity->name(),
                        Map::of(
                            ...$entity
                                ->properties()
                                ->map(static fn($property) => [$property->name(), $property])
                                ->toList(),
                        ),
                    ],
                )
                ->toList(),
        );
    }

    public function __invoke(Specification $specification): Specification
    {
        if ($specification instanceof Not) {
            return $this($specification->specification())->not();
        }

        if ($specification instanceof Composite) {
            $left = $this($specification->left());
            $right = $this($specification->right());

            return match ($specification->operator()) {
                Operator::and => $left->and($right),
                Operator::or => $left->or($right),
            };
        }

        if (!($specification instanceof Comparator)) {
            $class = $specification::class;

            throw new \LogicException("Unsupported specification '$class'");
        }

        $property = Str::of($specification->property());

        if ($property->contains('.')) {
            /**
             * @psalm-suppress PossiblyUndefinedArrayOffset
             * @var Str $entity
             * @var Str $property
             */
            [$entity, $property] = $property->split('.')->toList();
            /** @psalm-suppress ArgumentTypeCoercion */
            $properties = $this->entities->get($entity->toString())->match(
                static fn($properties) => $properties,
                static fn() => throw new \LogicException("Unknown entity '$entity'"),
            );

            /** @psalm-suppress ArgumentTypeCoercion */
            return Entity::of(
                $entity->toString(),
                $property->toString(),
                $specification->sign(),
                $this->normalizeProperty(
                    $properties,
                    $property->toString(),
                    $specification->value(),
                ),
            );
        }

        return $this->normalize($specification);
    }

    /**
     * @template A of object
     *
     * @param Aggregate<A> $definition
     *
     * @return self<A>
     */
    public static function of(Aggregate $definition): self
    {
        return new self($definition);
    }

    private function normalize(Comparator $specification): Property
    {
        $property = $specification->property();
        /** @var mixed */
        $value = $specification->value();

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress MixedMethodCall
         * @psalm-suppress MixedInferredReturnType
         * @psalm-suppress MixedReturnStatement
         */
        return Property::of(
            $property,
            $specification->sign(),
            match ($property) {
                $this->definition->id()->property() => match (true) {
                    \is_array($value) => \array_values(\array_map(
                        static fn($value): string => $value->toString(),
                        $value,
                    )),
                    $value instanceof Set, $value instanceof Sequence => $value
                        ->keep(Instance::of(Id::class))
                        ->map(static fn($value) => $value->toString())
                        ->toList(),
                    default => $value->toString(),
                },
                default => $this->normalizeProperty(
                    $this->properties,
                    $property,
                    $value,
                ),
            },
        );
    }

    /**
     * @param Map<non-empty-string, Aggregate\Property> $properties
     * @param non-empty-string $property
     *
     * @return null|string|int|bool|list<string|int|bool|null>
     */
    private function normalizeProperty(
        Map $properties,
        string $property,
        mixed $value,
    ): null|string|int|bool|array {
        return $properties
            ->get($property)
            ->map(static fn($property) => match (true) {
                \is_array($value) => \array_values(\array_map(
                    $property->type()->normalize(...),
                    $value,
                )),
                $value instanceof Set, $value instanceof Sequence => $value
                    ->map($property->type()->normalize(...))
                    ->toList(),
                default => $property->type()->normalize($value),
            })
            ->match(
                static fn($value) => $value,
                static fn() => throw new \LogicException("Unknown property '$property'"),
            );
    }
}
