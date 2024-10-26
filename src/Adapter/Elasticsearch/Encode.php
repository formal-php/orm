<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Raw\{
    Aggregate,
    Diff,
};
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

    public function __invoke(Diff|Aggregate $data): Content
    {
        $properties = $this->properties($data->properties());
        $entities = $data
            ->entities()
            ->exclude(static fn($entity) => $entity->properties()->empty())
            ->map(static fn($entity) => [
                $entity->name() => self::properties($entity->properties()),
            ])
            ->toList();
        $optionals = $data
            ->optionals()
            ->exclude(static fn($optional) => $optional->properties()->match(
                static fn($properties) => $properties->empty(),
                static fn() => false, // force setting the property to null below
            ))
            ->map(static fn($optional) => [
                $optional->name() => $optional->properties()->match(
                    self::properties(...),
                    static fn() => null,
                ),
            ])
            ->toList();
        $collections = $data
            ->collections()
            ->map(static fn($collection) => [
                $collection->name() => $collection
                    ->entities()
                    ->unsorted()
                    ->map(static fn($entity) => self::properties($entity->properties()))
                    ->toList(),
            ])
            ->toList();

        $document = \array_merge(
            [$data->id()->name() => $data->id()->value()],
            $properties,
            ...$entities,
            ...$optionals,
            ...$collections,
        );

        if ($data instanceof Diff) {
            $document = ['doc' => $document];
        }

        return Content::ofString(Json::encode($document));
    }

    public static function new(): self
    {
        return new self;
    }

    /**
     * @param Sequence<Aggregate\Property> $properties
     */
    private static function properties(Sequence $properties): array
    {
        return \array_merge(
            ...$properties
                ->map(static fn($property) => [$property->name() => $property->value()])
                ->toList(),
        );
    }
}
