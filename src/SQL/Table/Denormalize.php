<?php
declare(strict_types = 1);

namespace Formal\ORM\SQL\Table;

use Formal\ORM\{
    Definition\Aggregate,
    SQL\Types,
    SQL\Type,
};
use Formal\AccessLayer\Row;
use Innmind\Reflection\{
    ReflectionClass,
    InjectionStrategy,
    Instanciator,
};
use Innmind\Immutable\Map;

/**
 * @template T of object
 */
final class Denormalize
{
    /** @var Aggregate<T> */
    private Aggregate $aggregate;
    private Types $types;
    /** @var ?ReflectionClass<T> */
    private ?ReflectionClass $reflection;
    /** @var ?Map<string, Type> */
    private ?Map $properties;

    /**
     * @param Aggregate<T> $aggregate
     */
    public function __construct(Aggregate $aggregate, Types $types)
    {
        $this->aggregate = $aggregate;
        $this->types = $types;
    }

    /**
     * @return T
     */
    public function __invoke(Row $row): object
    {
        /** @var T */
        return $this
            ->properties()
            ->reduce(
                $this->reflection(),
                static fn(ReflectionClass $reflection, $property, $type) => $reflection->withProperty(
                    $property,
                    $type->denormalize($row->column($property)),
                ),
            )
            ->build();
    }

    /**
     * @return ReflectionClass<T>
     */
    private function reflection(): ReflectionClass
    {
        return $this->reflection ??= ReflectionClass::of(
            $this->aggregate->class(),
            null,
            new InjectionStrategy\ReflectionStrategy,
            new Instanciator\ConstructorLessInstanciator,
        );
    }

    /**
     * @return Map<string, Type>
     */
    private function properties(): Map
    {
        return $this->properties ??= $this->aggregate->properties()->toMapOf(
            'string',
            Type::class,
            fn($property) => yield $property->name() => ($this->types)($property),
        );
    }
}
