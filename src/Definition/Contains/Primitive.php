<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Contains;

use Innmind\Type\Primitive as Type;

/**
 * @psalm-immutable
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Primitive
{
    /**
     * @param 'string'|'int'|'float'|'bool' $type
     */
    public function __construct(
        private string $type,
    ) {
    }

    /**
     * @internal
     */
    public function type(): Type
    {
        return match ($this->type) {
            'string' => Type::string(),
            'int' => Type::int(),
            'float' => Type::float(),
            'bool' => Type::bool(),
        };
    }
}
