<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\SQL;

use Formal\ORM\Definition\Type;
use Formal\AccessLayer\Table;

/**
 * @psalm-immutable
 */
final class MapType
{
    private function __construct()
    {
    }

    public function __invoke(Type $type): Table\Column\Type
    {
        return match (true) {
            $type instanceof SQLType => $type->sqlType(),
            $type instanceof Type\NullableType,
            $type instanceof Type\MaybeType => $this($type->inner())->nullable(),
            $type instanceof Type\BoolType => Table\Column\Type::tinyint(1)
                ->comment('Boolean'),
            $type instanceof Type\IdType => Table\Column\Type::varchar(36)
                ->comment('UUID'),
            $type instanceof Type\IntType => Table\Column\Type::bigint()
                ->comment('TODO Adjust the size depending on your use case'),
            $type instanceof Type\PointInTimeType => Table\Column\Type::varchar(32)
                ->comment('Date with timezone down to the microsecond'),
            default => Table\Column\Type::longtext()
                ->comment('TODO adjust the type depending on your use case'),
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
