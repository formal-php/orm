<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

/**
 * @internal
 */
final class Id
{
    private string $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function property(): string
    {
        return $this->property;
    }
}
