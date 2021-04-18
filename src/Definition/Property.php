<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Formal\ORM\{
    Definition\Property\Type,
    Id,
};

/**
 * @internal
 */
final class Property
{
    private string $name;
    private Type $type;

    private function __construct(string $name, Type $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @param class-string $class
     */
    public static function of(string $class, string $property): self
    {
        return new self(
            $property,
            Type::of($class, $property),
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): Type
    {
        return $this->type;
    }

    /**
     * If it's the id to reference the aggregate.
     *
     * Ids that references relations will return false
     */
    public function isId(): bool
    {
        return $this->type->ofClass(Id::class);
    }
}
