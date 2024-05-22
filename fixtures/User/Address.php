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

    private function __construct(string $value)
    {
        $this->value = $value;
        $this->sortable = new Sortable($value);
    }

    public static function new(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
