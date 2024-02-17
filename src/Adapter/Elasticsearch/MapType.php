<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Definition\Type;

/**
 * @psalm-immutable
 */
final class MapType
{
    private function __construct()
    {
    }

    public function __invoke(Type $type): array
    {
        return match (true) {
            $type instanceof ElasticsearchType => $type->elasticsearchType(),
            $type instanceof Type\NullableType,
            $type instanceof Type\MaybeType => $this($type->inner()),
            $type instanceof Type\BoolType => ['type' => 'boolean'],
            $type instanceof Type\IdType => ['type' => 'keyword'],
            $type instanceof Type\IntType => ['type' => 'long'],
            default => ['type' => 'text'],
        };
    }

    /**
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self;
    }
}
