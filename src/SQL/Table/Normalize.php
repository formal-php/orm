<?php
declare(strict_types = 1);

namespace Formal\ORM\SQL\Table;

use Formal\ORM\{
    Definition\Aggregate,
    SQL\Types,
    SQL\Type,
};
use Innmind\Reflection\{
    ReflectionObject,
    ExtractionStrategy,
};
use function Innmind\Immutable\unwrap;

/**
 * @template T of object
 */
final class Normalize
{
    /** @var Aggregate<T> */
    private Aggregate $aggregate;
    private Types $types;
    /** @var ?list<string> */
    private ?array $properties = null;
    /** @var ?array<string, Type> */
    private ?array $propertiesType = null;

    /**
     * @param Aggregate<T> $aggregate
     */
    public function __construct(Aggregate $aggregate, Types $types)
    {
        $this->aggregate = $aggregate;
        $this->types = $types;
    }

    /**
     * @param T $entity
     *
     * @return array<string, mixed>
     */
    public function __invoke(object $entity): array
    {
        $properties = $this->properties();
        $types = $this->propertiesType();

        /** @var array<string, mixed> */
        return ReflectionObject::of(
            $entity,
            null,
            null,
            new ExtractionStrategy\ReflectionStrategy,
        )
            ->extract(...$properties)
            ->map(static fn($property, $value): mixed => $types[$property]->normalize($value))
            ->reduce(
                [],
                static function(array $values, $property, $value): array {
                    /** @var mixed */
                    $values[$property] = $value;

                    return $values;
                },
            );
    }

    /**
     * @return list<string>
     */
    private function properties(): array
    {
        return $this->properties ??= unwrap(
            $this->aggregate->properties()->mapTo(
                'string',
                static fn($property) => $property->name(),
            ),
        );
    }

    /**
     * @return array<string, Type>
     */
    private function propertiesType(): array
    {
        /**
         * @psalm-suppress MixedPropertyTypeCoercion
         * @var array<string, Type>
         */
        return $this->propertiesType ??= $this->aggregate->properties()->reduce(
            [],
            function(array $properties, $property): array {
                $properties[$property->name()] = ($this->types)($property);

                return $properties;
            },
        );
    }
}
