<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Innmind\Type\{
    Type,
    ClassName,
};

/**
 * @psalm-immutable
 */
#[\Attribute]
final class Contains
{
    /** @var class-string */
    private string $type;

    /**
     * @param class-string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function type(): Type
    {
        return ClassName::of($this->type);
    }
}
