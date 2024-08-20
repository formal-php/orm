<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
    Specification\Property as PropertySpecification,
    Specification\Entity as EntitySpecification,
    Specification\Child as ChildSpecification,
    Specification\Just as JustSpecification,
    Specification\Has as HasSpecification,
};
use Innmind\Specification\{
    Specification,
    Not,
    Composite,
    Comparator,
    Operator,
    Sign,
};
use Innmind\Immutable\Sequence;

/**
 * @internal
 * @psalm-immutable
 * @template T of object
 */
final class Fold
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
     * @return callable(Aggregate): bool
     */
    public function __invoke(Specification $specification): callable
    {
        if ($specification instanceof Not) {
            $filter = $this($specification->specification());

            return static fn(Aggregate $aggregate) => !$filter($aggregate);
        }

        if ($specification instanceof Composite) {
            $left = $this($specification->left());
            $right = $this($specification->right());

            return match ($specification->operator()) {
                Operator::and => static fn(Aggregate $aggregate) => $left($aggregate) && $right($aggregate),
                Operator::or => static fn(Aggregate $aggregate) => $left($aggregate) || $right($aggregate),
            };
        }

        if ($specification instanceof EntitySpecification) {
            $filter = $this->child($specification->specification());

            return static fn(Aggregate $aggregate) => $aggregate
                ->entities()
                ->find(static fn($entity) => $entity->name() === $specification->entity())
                ->match(
                    static fn($entity) => $filter($entity->properties()),
                    static fn() => false,
                );
        }

        if ($specification instanceof ChildSpecification) {
            $filter = $this->child($specification->specification());

            return static fn(Aggregate $aggregate) => $aggregate
                ->collections()
                ->find(static fn($collection) => $collection->name() === $specification->collection())
                ->flatMap(
                    static fn($collection) => $collection
                        ->entities()
                        ->map(static fn($entity) => $entity->properties())
                        ->find($filter),
                )
                ->match(
                    static fn() => true,
                    static fn() => false,
                );
        }

        if ($specification instanceof JustSpecification) {
            $filter = $this->child($specification->specification());

            return static fn(Aggregate $aggregate) => $aggregate
                ->optionals()
                ->find(static fn($optional) => $optional->name() === $specification->optional())
                ->flatMap(static fn($optional) => $optional->properties())
                ->match(
                    static fn($properties) => $filter($properties),
                    static fn() => false,
                );
        }

        if ($specification instanceof HasSpecification) {
            return static fn(Aggregate $aggregate) => $aggregate
                ->optionals()
                ->find(static fn($optional) => $optional->name() === $specification->optional())
                ->flatMap(static fn($optional) => $optional->properties())
                ->match(
                    static fn() => true,
                    static fn() => false,
                );
        }

        if (!($specification instanceof PropertySpecification)) {
            $class = $specification::class;

            throw new \LogicException("Unsupported specification '$class'");
        }

        $filter = $this->filter($specification);

        if ($specification->property() === $this->definition->id()->property()) {
            return static fn(Aggregate $aggregate) => $filter($aggregate->id()->value());
        }

        return static fn(Aggregate $aggregate) => $aggregate
            ->properties()
            ->find(static fn($property) => $property->name() === $specification->property())
            ->match(
                static fn($property) => $filter($property->value()),
                static fn() => false,
            );
    }

    /**
     * @internal
     * @psalm-pure
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

    /**
     * @return callable(null|string|int|bool): bool
     */
    private function filter(Comparator $specification): callable
    {
        /** @psalm-suppress MixedArgument */
        return match ($specification->sign()) {
            Sign::equality => static fn(null|string|int|bool $value): bool => $value === $specification->value(),
            Sign::lessThan => static fn(null|string|int|bool $value): bool => $value < $specification->value(),
            Sign::moreThan => static fn(null|string|int|bool $value): bool => $value > $specification->value(),
            Sign::startsWith => static fn(null|string|int|bool $value): bool => \is_string($value) && \str_starts_with($value, $specification->value()),
            Sign::endsWith => static fn(null|string|int|bool $value): bool => \is_string($value) && \str_ends_with($value, $specification->value()),
            Sign::contains => static fn(null|string|int|bool $value): bool => \is_string($value) && \str_contains($value, $specification->value()),
            Sign::in => static fn(null|string|int|bool $value): bool => \in_array($value, $specification->value(), true),
        };
    }

    /**
     * @return callable(Sequence<Aggregate\Property>): bool
     */
    private function child(Specification $specification): callable
    {
        if ($specification instanceof Not) {
            $filter = $this->child($specification->specification());

            return static fn(Sequence $properties) => !$filter($properties);
        }

        if ($specification instanceof Composite) {
            $left = $this->child($specification->left());
            $right = $this->child($specification->right());

            /** @psalm-suppress MixedArgumentTypeCoercion */
            return match ($specification->operator()) {
                Operator::and => static fn(Sequence $properties) => $left($properties) && $right($properties),
                Operator::or => static fn(Sequence $properties) => $left($properties) || $right($properties),
            };
        }

        if (!($specification instanceof PropertySpecification)) {
            $class = $specification::class;

            throw new \LogicException("Unsupported specification '$class'");
        }

        $filter = $this->filter($specification);

        return static fn(Sequence $properties) => $properties
            ->find(static fn($property) => $property->name() === $specification->property())
            ->match(
                static fn($property) => $filter($property->value()),
                static fn() => false,
            );
    }
}
