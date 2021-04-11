<?php
declare(strict_types = 1);

namespace Formal\ORM\SQL\Type;

use Formal\ORM\{
    SQL\Type,
    Definition\Property,
    Id as Identifier,
};
use Formal\AccessLayer\Table\Column;

/**
 * @implements Type<string, string>
 */
final class Str implements Type
{
    private bool $nullable = false;

    public static function nullable(): self
    {
        $self = new self;
        $self->nullable = true;

        return $self;
    }

    public function normalize(mixed $value): mixed
    {
        return $value;
    }

    public function denormalize(mixed $value): mixed
    {
        return $value;
    }

    public function declaration(Property $property): Column
    {
        $type = Column\Type::text();

        $type = match($this->nullable) {
            true => $type->nullable(),
            false => $type,
        };

        return new Column(
            new Column\Name($property->name()),
            $type,
        );
    }

    public function type(): string
    {
        return match($this->nullable) {
            true => '?string',
            false => 'string',
        };
    }
}
