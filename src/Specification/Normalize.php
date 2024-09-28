<?php
declare(strict_types = 1);

namespace Formal\ORM\Specification;

use Formal\ORM\{
    Definition\Aggregate,
    Repository\Context,
    Id,
    Matching,
    Adapter\Repository\SubMatch,
};
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Not,
    Operator,
    Sign,
};
use Innmind\Immutable\{
    Set,
    Sequence,
    Map,
    Either,
    Predicate\Instance,
};

/**
 * @internal
 * @psalm-immutable
 * @template T of object
 */
final class Normalize
{
    /** @var Aggregate<T> */
    private Aggregate $definition;
    private Context $context;
    /** @var Map<non-empty-string, Aggregate\Property<T, mixed>> */
    private Map $properties;
    /** @var Map<non-empty-string, Map<non-empty-string, Aggregate\Property>> */
    private Map $entities;
    /** @var Map<non-empty-string, Map<non-empty-string, Aggregate\Property>> */
    private Map $optionals;
    /** @var Map<non-empty-string, Map<non-empty-string, Aggregate\Property>> */
    private Map $collections;

    /**
     * @param Aggregate<T> $definition
     */
    private function __construct(
        Aggregate $definition,
        Context $context,
    ) {
        $this->definition = $definition;
        $this->context = $context;
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
        $this->collections = Map::of(
            ...$definition
                ->collections()
                ->map(
                    static fn($collection) => [
                        $collection->name(),
                        Map::of(
                            ...$collection
                                ->properties()
                                ->map(static fn($property) => [$property->name(), $property])
                                ->toList(),
                        ),
                    ],
                )
                ->toList(),
        );
        $this->optionals = Map::of(
            ...$definition
                ->optionals()
                ->map(
                    static fn($optional) => [
                        $optional->name(),
                        Map::of(
                            ...$optional
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

        if ($specification instanceof Child) {
            return $this
                ->collections
                ->get($specification->collection())
                ->map(fn($collection) => $this->child(
                    $collection,
                    $specification->specification(),
                ))
                ->match(
                    static fn($normalized) => Child::of(
                        $specification->collection(),
                        $normalized,
                    ),
                    static fn() => throw new \LogicException("Unknown collection '{$specification->collection()}'"),
                );
        }

        if ($specification instanceof Just) {
            return $this
                ->optionals
                ->get($specification->optional())
                ->map(fn($optional) => $this->child(
                    $optional,
                    $specification->specification(),
                ))
                ->match(
                    static fn($normalized) => Just::of(
                        $specification->optional(),
                        $normalized,
                    ),
                    static fn() => throw new \LogicException("Unknown optional '{$specification->optional()}'"),
                );
        }

        if ($specification instanceof Has) {
            return $this
                ->optionals
                ->get($specification->optional())
                ->match(
                    static fn() => $specification,
                    static fn() => throw new \LogicException("Unknown optional '{$specification->optional()}'"),
                );
        }

        if ($specification instanceof Entity) {
            return $this
                ->entities
                ->get($specification->entity())
                ->map(fn($entity) => $this->child(
                    $entity,
                    $specification->specification(),
                ))
                ->match(
                    static fn($normalized) => Entity::of(
                        $specification->entity(),
                        $normalized,
                    ),
                    static fn() => throw new \LogicException("Unknown entity '{$specification->entity()}'"),
                );
        }

        if (!($specification instanceof Comparator)) {
            $class = $specification::class;

            throw new \LogicException("Unsupported specification '$class'");
        }

        return $this->normalize($specification);
    }

    /**
     * @internal
     * @psalm-pure
     * @template A of object
     *
     * @param Aggregate<A> $definition
     *
     * @return self<A>
     */
    public static function of(
        Aggregate $definition,
        Context $context,
    ): self {
        return new self($definition, $context);
    }

    private function normalize(Comparator $specification): Specification
    {
        return $this
            ->subMatch($specification)
            ->match(
                static fn($specification) => $specification,
                /**
                 * @psalm-suppress MixedArgument
                 * @psalm-suppress MixedMethodCall
                 * @psalm-suppress MixedInferredReturnType
                 * @psalm-suppress MixedReturnStatement
                 */
                fn(mixed $value) => Property::of(
                    $specification->property(),
                    $specification->sign(),
                    match ($specification->property()) {
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
                            $specification->property(),
                            $value,
                        ),
                    },
                ),
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

    /**
     * @param Map<non-empty-string, Aggregate\Property> $properties
     */
    private function child(
        Map $properties,
        Specification $specification,
    ): Specification {
        if ($specification instanceof Not) {
            return $this
                ->child($properties, $specification->specification())
                ->not();
        }

        if ($specification instanceof Composite) {
            $left = $this->child($properties, $specification->left());
            $right = $this->child($properties, $specification->right());

            return match ($specification->operator()) {
                Operator::and => $left->and($right),
                Operator::or => $left->or($right),
            };
        }

        if (!($specification instanceof Comparator)) {
            $class = $specification::class;

            throw new \LogicException("Unsupported specification '$class'");
        }

        return $this
            ->subMatch($specification)
            ->match(
                static fn($specification) => $specification,
                fn($value) => Property::of(
                    $specification->property(),
                    $specification->sign(),
                    $this->normalizeProperty(
                        $properties,
                        $specification->property(),
                        $value,
                    ),
                ),
            );
    }

    /**
     * @return Either<mixed, Specification>
     */
    private function subMatch(Comparator $specification): Either
    {
        if ($specification->sign() !== Sign::in) {
            return Either::left($specification->value());
        }

        if (!($specification->value() instanceof Matching)) {
            return Either::left($specification->value());
        }

        $value = $specification
            ->value()
            ->crossAggregateSearch($this->context);

        if (!($value instanceof SubMatch)) {
            return Either::left($value);
        }

        return Either::right(CrossMatch::of(
            $specification->property(),
            $value,
        ));
    }
}
