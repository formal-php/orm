<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Filesystem;

use Formal\ORM\Raw\Aggregate;
use Innmind\Filesystem\File\{
    File,
    Content,
};
use Innmind\Json\Json;

final class Encode
{
    private function __construct()
    {
    }

    public function __invoke(Aggregate $data): File
    {
        return File::named(
            $data->id()->value(),
            Content\Lines::ofContent(Json::encode([
                'properties' => $data
                    ->properties()
                    ->map(static fn($property) => [$property->name(), $property->value()])
                    ->toList(),
                'entities' => $data
                    ->entities()
                    ->map(
                        static fn($entity) => [
                            $entity->name(),
                            $entity
                                ->properties()
                                ->map(static fn($property) => [$property->name(), $property->value()])
                                ->toList(),
                        ],
                    )
                    ->toList(),
            ])),
        );
    }

    public static function new(): self
    {
        return new self;
    }
}
