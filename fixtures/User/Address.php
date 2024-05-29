<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM\User;

use Fixtures\Formal\ORM\Sortable;

final class Address
{
    private string $value;
    /**
     * This property is necessary for the Elasticsearch adapter that requires
     * the field being sorted on to be a keyword
     */
    private Sortable $sortable;
    private ?int $id = null;
    /**
     * This property with a `false` value exist to showcase 2 bugs:
     * - when not specifying the parameter type for SQL it fails to correctly coalesce it to `0`
     * - when failing to SQL insert an entity inside a collection it silently fails
     */
    private bool $enabled;

    private function __construct(string $value, bool $enabled)
    {
        $this->value = $value;
        $this->sortable = new Sortable($value);
        $this->enabled = $enabled;
    }

    public static function new(string $value): self
    {
        return new self($value, true);
    }

    public function disable(): self
    {
        return new self($this->value, false);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
