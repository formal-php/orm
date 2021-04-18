<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

/**
 * @todo Extract this into a dedicated project
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Template
{
    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function of(string $type): bool
    {
        return $this->type === $type;
    }
}
