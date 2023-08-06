<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw\Aggregate,
};
use Formal\AccessLayer\{
    Row,
};
use Innmind\Immutable\{
    Maybe,
    Set,
    Str,
};

/**
 * @template T of object
 */
final class Decode
{
    /** @var Definition<T> */
    private Definition $definition;
    /** @var non-empty-string */
    private string $id;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->id = 'entity_'.$definition->id()->property();
    }

    /**
     * @return callable(Row): Maybe<Aggregate>
     */
    public function __invoke(Aggregate\Id $id = null): callable
    {
        /** @psalm-suppress MixedArgument */
        $id = match ($id) {
            null => fn(Row $row) => $row
                ->column($this->id)
                ->filter(\is_string(...))
                ->map(fn($id) => Aggregate\Id::of(
                    $this->id,
                    $id,
                )),
            default => static fn(Row $row) => Maybe::just($id),
        };

        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress ArgumentTypeCoercion
         */
        return fn(Row $row) => $id($row)
            ->map(fn($id) => Aggregate::of(
                $id,
                $row
                    ->values()
                    ->filter(static fn($value) => Str::of($value->column()->toString())->startsWith('entity_'))
                    ->map(static fn($value) => Aggregate\Property::of(
                        Str::of($value->column()->toString())->drop(7)->toString(),
                        $value->value(),
                    ))
                    ->toSet(),
                $this
                    ->definition
                    ->entities()
                    ->map(
                        static fn($entity) => Aggregate\Entity::of(
                            $entity->name(),
                            $row
                                ->values()
                                ->filter(static fn($value) => Str::of($value->column()->toString())->startsWith($entity->name().'_'))
                                ->map(static fn($value) => Aggregate\Property::of(
                                    Str::of($value->column()->toString())
                                        ->drop(Str::of($entity->name())->length() + 1)
                                        ->toString(),
                                    $value->value(),
                                ))
                                ->toSet(),
                        ),
                    ),
                Set::of(), // TODO
                Set::of(), // TODO
            ));
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
}
