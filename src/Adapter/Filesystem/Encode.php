<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\Raw\{
    Aggregate,
    Diff,
};
use Innmind\Filesystem\{
    Directory,
    File,
    File\Content,
    Name,
};
use Innmind\Json\Json;

/**
 * @internal
 */
final class Encode
{
    private function __construct()
    {
    }

    public function __invoke(Aggregate|Diff $data): Directory
    {
        return Directory::named($data->id()->value())
            ->add(
                $data
                    ->properties()
                    ->map(static fn($property) => File::named(
                        $property->name(),
                        Content::ofString(Json::encode($property->value())),
                    ))
                    ->reduce(
                        Directory::named('properties'),
                        static fn(Directory $properties, $property) => $properties->add($property),
                    ),
            )
            ->add(
                $data
                    ->entities()
                    ->map(
                        static fn($entity) => $entity
                            ->properties()
                            ->map(static fn($property) => File::named(
                                $property->name(),
                                Content::ofString(Json::encode($property->value())),
                            ))
                            ->reduce(
                                Directory::named($entity->name()),
                                static fn(Directory $entity, $property) => $entity->add($property),
                            ),
                    )
                    ->reduce(
                        Directory::named('entities'),
                        static fn(Directory $entities, $entity) => $entities->add($entity),
                    ),
            )
            ->add(
                $data
                    ->optionals()
                    ->map(
                        static fn($optional) => $optional
                            ->properties()
                            ->map(
                                static fn($properties) => $properties
                                    ->map(static fn($property) => File::named(
                                        $property->name(),
                                        Content::ofString(Json::encode($property->value())),
                                    ))
                                    ->reduce(
                                        Directory::named('just'),
                                        static fn(Directory $properties, $property) => $properties->add($property),
                                    ),
                            )
                            ->match(
                                static fn($properties) => Directory::named($optional->name())->add($properties),
                                static fn() => Directory::named($optional->name())->remove(Name::of('just')), // erase previous data
                            ),
                    )
                    ->reduce(
                        Directory::named('optionals'),
                        static fn(Directory $optionals, $optional) => $optionals->add($optional),
                    ),
            )
            ->add(
                // Each collection is stored in a signle file to make sure no
                // previous stored data is kept on the filesystem. Another
                // solution would be to store each entity of the collection to a
                // dedicated directory/file but there is currently no way to
                // tell the filesystem to erase all files in a directory before
                // persisting the new version.
                $data
                    ->collections()
                    ->map(
                        static fn($collection) => File::named(
                            $collection->name(),
                            Content::ofString(Json::encode(
                                $collection
                                    ->entities()
                                    ->map(
                                        static fn($entity) => $entity
                                            ->map(static fn($property) => [
                                                $property->name(),
                                                $property->value(),
                                            ])
                                            ->toList(),
                                    )
                                    ->toList(),
                            )),
                        ),
                    )
                    ->reduce(
                        Directory::named('collections'),
                        static fn(Directory $collections, $collection) => $collections->add($collection),
                    ),
            );
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self;
    }
}
