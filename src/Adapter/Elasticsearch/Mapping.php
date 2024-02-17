<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Definition\Aggregate as Definition;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @template T of object
 */
final class Mapping
{
    /** @var Definition<T> */
    private Definition $definition;
    private MapType $mapType;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->definition = $definition;
        $this->mapType = MapType::new();
    }

    public function __invoke(): array
    {
        $properties = $this->properties($this->definition->properties());
        $entities = $this
            ->definition
            ->entities()
            ->map(fn($entity) => [
                $entity->name() => [
                    'properties' => $this->properties($entity->properties()),
                ],
            ])
            ->toList();
        $optionals = $this
            ->definition
            ->optionals()
            ->map(fn($optional) => [
                $optional->name() => [
                    'properties' => $this->properties($optional->properties()),
                ],
            ])
            ->toList();
        $collections = $this
            ->definition
            ->collections()
            ->map(fn($optional) => [
                $optional->name() => [
                    'type' => 'nested',
                    'properties' => $this->properties($optional->properties()),
                ],
            ])
            ->toList();

        return ['properties' => \array_merge(
            [$this->definition->id()->property() => [
                'type' => 'keyword',
                'index' => false,
            ]],
            $properties,
            ...$entities,
            ...$optionals,
            ...$collections,
        )];
    }

    /**
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
