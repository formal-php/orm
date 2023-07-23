<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
    Specification\Property as PropertySpecification,
    Specification\Entity as EntitySpecification,
};
use Innmind\Specification\{
    Specification,
    Not,
    Composite,
    Comparator,
    Operator,
    Sign,
};

/**
 * @internal
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
            $filter = $this->filter($specification);

            return static fn(Aggregate $aggregate) => $aggregate
                ->entities()
                ->find(static fn($entity) => $entity->name() === $specification->entity())
                ->flatMap(
                    static fn($entity) => $entity
                        ->properties()
                        ->find(static fn($property) => $property->name() === $specification->property()),
                )
                ->match(
                    static fn($property) => $filter($property->value()),
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
            Sign::inequality => static fn(null|string|int|bool $value): bool => $value !== $specification->value(),
            Sign::lessThan => static fn(null|string|int|bool $value): bool => $value < $specification->value(),
            Sign::moreThan => static fn(null|string|int|bool $value): bool => $value > $specification->value(),
            Sign::lessThanOrEqual => static fn(null|string|int|bool $value): bool => $value <= $specification->value(),
            Sign::moreThanOrEqual => static fn(null|string|int|bool $value): bool => $value >= $specification->value(),
            Sign::isNull => static fn(null|string|int|bool $value): bool => \is_null($value),
            Sign::isNotNull => static fn(null|string|int|bool $value): bool => !\is_null($value),
            Sign::startsWith => static fn(null|string|int|bool $value): bool => \is_string($value) && \str_starts_with($value, $specification->value()),
            Sign::endsWith => static fn(null|string|int|bool $value): bool => \is_string($value) && \str_ends_with($value, $specification->value()),
            Sign::contains => static fn(null|string|int|bool $value): bool => \is_string($value) && \str_contains($value, $specification->value()),
            Sign::in => static fn(null|string|int|bool $value): bool => \in_array($value, $specification->value(), true),
        };
    }
}
