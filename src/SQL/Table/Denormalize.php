<?php
declare(strict_types = 1);

namespace Formal\ORM\SQL\Table;

use Formal\ORM\{
    Definition\Aggregate,
    SQL\Types,
    SQL\Type,
};
use Formal\AccessLayer\Row;
use Innmind\Immutable\Map;

/**
 * @template T of object
 */
final class Denormalize
{
    /** @var Aggregate<T> */
    private Aggregate $aggregate;
    private Types $types;
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
        return $this->aggregate->denormalize(
            $this->properties()->toMapOf(
                'string',
                'mixed',
                static fn($property, $type) => yield $property => $type->denormalize(
                    $row->column($property),
                ),
            ),
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
