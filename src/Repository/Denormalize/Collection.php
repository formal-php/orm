<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository\Denormalize;

use Formal\ORM\{
    Definition\Aggregate\Collection as Definition,
    Definition\Aggregate\Property,
    Raw\Aggregate\Collection as Raw,
};
use Innmind\Reflection\Instanciate;
use Innmind\Immutable\{
    Map,
    Set,
};

/**
 * @internal
 * @template T of object
 */
final class Collection
{
    /** @var Definition<T> */
    private Definition $definition;
    private Instanciate $instanciate;
    /** @var Map<non-empty-string, Property<T, mixed>> */
    private Map $properties;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(
        Definition $definition,
        Instanciate $instanciate,
    ) {
        $this->definition = $definition;
        $this->instanciate = $instanciate;
        $this->properties = Map::of(
            ...$definition
                ->properties()
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );
    }

    /**
     * @return Set<T>
     */
    public function __invoke(Raw $collection): Set
    {
        if ($this->definition->enum()) {
            $class = $this->definition->class();

            return $collection
                ->entities()
                ->map(static function($entity) use ($class) {
                    $value = $entity
                        ->properties()
                        ->find(static fn($property) => $property->name() === 'name')
                        ->match(
                            static fn($property) => $property->value(),
                            static fn() => throw new \RuntimeException("Unable to denormalize collection of type '$class'"),
                        );

                    foreach ($class::cases() as $case) {
                        if ($case->name === $value) {
                            /** @var T */
                            return $case;
                        }
                    }

                    throw new \RuntimeException("Unable to denormalize collection of type '$class'");
                });
        }

        $class = $this->definition->class();
        $properties = $this->properties;
        $instanciate = $this->instanciate;

        return $collection
            ->entities()
            ->map(static function($entity) use ($class, $properties, $instanciate) {
                $entity = Map::of(
                    ...$entity
                        ->properties()
                        ->flatMap(
                            static fn($property) => $properties
                                ->get($property->name())
                                ->map(static fn($definition): mixed => $definition->type()->denormalize($property->value()))
                                ->map(static fn($value) => [$property->name(), $value])
                                ->toSequence(),
                        )
                        ->toList(),
                );

                /** @var T */
                return $instanciate($class, $entity)->match(
                    static fn($object) => $object,
                    static fn() => throw new \RuntimeException("Unable to denormalize collection of type '$class'"),
                );
            });
    }

    /**
     * @internal
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(
        Definition $definition,
        Instanciate $instanciate,
    ): self {
        return new self($definition, $instanciate);
    }
}
