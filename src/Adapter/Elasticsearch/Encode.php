<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Raw\Aggregate;
use Innmind\Filesystem\File\Content;
use Innmind\Json\Json;
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Encode
{
    private function __construct()
    {
    }

    public function __invoke(Aggregate $data): Content
    {
        $properties = $this->properties($data->properties());
        $entities = $data
            ->entities()
            ->map(fn($entity) => [
                $entity->name() => $this->properties($entity->properties()),
            ])
            ->toList();
        $optionals = $data
            ->optionals()
            ->map(fn($optional) => [
                $optional->name() => $optional->properties()->match(
                    $this->properties(...),
                    static fn() => null,
                ),
            ])
            ->toList();
        $collections = $data
            ->collections()
            ->map(fn($collection) => [
                $collection->name() => $collection
                    ->entities()
                    ->map(fn($entity) => $this->properties($entity->properties()))
                    ->toList(),
            ])
            ->toList();

        return Content::ofString(Json::encode(\array_merge(
            [$data->id()->name() => $data->id()->value()],
            $properties,
            ...$entities,
            ...$optionals,
            ...$collections,
        )));
    }

    public static function new(): self
    {
        return new self;
    }

    /**
     * @param Sequence<Aggregate\Property> $properties
     */
    private function properties(Sequence $properties): array
    {
        return \array_merge(
            ...$properties
                ->map(static fn($property) => [$property->name() => $property->value()])
                ->toList(),
        );
    }
}
