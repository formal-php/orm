<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Innmind\Type\ClassName;

/**
 * @psalm-immutable
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Contains
{
    /**
     * @param class-string $type
     */
    public function __construct(private string $type)
    {
    }

    /**
     * @internal
     */
    public function type(): ClassName
    {
        return match ((new \ReflectionClass($this->type))->isEnum()) {
            true => ClassName::ofEnum($this->type),
            false => ClassName::of($this->type),
        };
    }
}
