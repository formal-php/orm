<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Definition\Aggregate as Definition;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Mapping
{
    private MapType $mapType;

    private function __construct()
    {
        $this->mapType = MapType::new();
    }

    public function __invoke(Definition $definition): array
    {
        $properties = $this->properties($definition->properties());
        $entities = $definition
            ->entities()
            ->map(fn($entity) => [
                $entity->name() => [
                    'properties' => $this->properties($entity->properties()),
                ],
            ])
            ->toList();
        $optionals = $definition
            ->optionals()
            ->map(fn($optional) => [
                $optional->name() => [
                    'properties' => $this->properties($optional->properties()),
                ],
            ])
            ->toList();
        $collections = $definition
            ->collections()
            ->map(fn($collection) => [
                $collection->name() => [
                    'type' => 'nested',
                    'properties' => [
                        'reference' => [
                            'type' => 'keyword',
                            'index' => false,
                        ],
                        'data' => [
                            'properties' => $this->properties($collection->properties()),
                        ],
                    ],
                ],
            ])
            ->toList();

        return ['properties' => \array_merge(
            [$definition->id()->property() => ['type' => 'keyword']],
            $properties,
            ...$entities,
            ...$optionals,
            ...$collections,
        )];
    }

    /**
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self;
    }

    /**
     * @param Sequence<Definition\Property> $properties
     */
    private function properties(Sequence $properties): array
    {
        return \array_merge(
            ...$properties
                ->map(fn($property) => [
                    $property->name() => ($this->mapType)($property->type()),
                ])
                ->toList(),
        );
    }
}
