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
 * @implements Type<Identifier<object>, string>
 */
final class Id implements Type
{
    private function __construct()
    {
    }

    public static function required(): self
    {
        return new self;
    }

    public function normalize(mixed $value): mixed
    {
        return $value->toString();
    }

    public function denormalize(mixed $value): mixed
    {
        /** @var Identifier<object> */
        return Identifier::of($value);
    }

    public function declaration(Property $property): Column
    {
        return new Column(
            new Column\Name($property->name()),
            Column\Type::char(36),
        );
    }

    public function type(): string
    {
        return Identifier::class;
    }
}
