<?php
declare(strict_types = 1);

namespace Formal\ORM\SQL\Table;

use Formal\ORM\{
    Definition\Aggregate,
    SQL\Types,
    SQL\Type,
};

/**
 * @template T of object
 */
final class Normalize
{
    /** @var Aggregate<T> */
    private Aggregate $aggregate;
    private Types $types;
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
        $types = $this->propertiesType();

        /** @var array<string, mixed> */
        return $this
            ->aggregate
            ->normalize($entity)
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
