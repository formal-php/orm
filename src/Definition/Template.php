<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

#[\Attribute]
final class Template
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

    /**
     * @param class-string $type
     */
    public function is(string $type): bool
    {
        return $this->type === $type;
    }
}
