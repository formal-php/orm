<?php
declare(strict_types = 1);

namespace Formal\ORM\Specification;

use Formal\ORM\Definition\Aggregate;
use Innmind\Specification\{
    Specification,
    Comparator,
    Composite,
    Not,
    Operator,
};

/**
 * @template T of object
 */
final class Normalize
{
    /** @var Aggregate<T> */
    private Aggregate $definition;

    /**
     * @param Aggregate<T> $definition
     */
    private function __construct(Aggregate $definition)
    {
        $this->definition = $definition;
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

        $property = $specification->property();

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress MixedMethodCall
         */
        return Property::of(
            $property,
            $specification->sign(),
            match ($property) {
                $this->definition->id()->property() => $specification->value()->toString(),
                default => $this
                    ->definition
                    ->properties()
                    ->find(static fn($definition) => $definition->name() === $property)
                    ->map(static fn($property) => $property->type()->normalize($specification->value()))
                    ->match(
                        static fn($value) => $value,
                        static fn() => throw new \LogicException("Unknown property '$property'"),
                    ),
            },
        );
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
}
