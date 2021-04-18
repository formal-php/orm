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
 * @implements Type<?Identifier<object>, ?string>
 */
final class Id implements Type
{
    private bool $nullable;

    private function __construct(bool $nullable)
    {
        $this->nullable = $nullable;
    }

    public static function required(): self
    {
        return new self(false);
    }

    public static function nullable(): self
    {
        return new self(true);
    }

    public function normalize(mixed $value): mixed
    {
        if (\is_null($value)) {
            return null;
        }

        return $value->toString();
    }

    public function denormalize(mixed $value): mixed
    {
        if (\is_null($value)) {
            return null;
        }

        /** @var Identifier<object> */
        return Identifier::of($value);
    }

    public function declaration(Property $property): Column
    {
        $type = Column\Type::char(36);

        return new Column(
            new Column\Name($property->name()),
            match($this->nullable) {
                true => $type->nullable(),
                false => $type,
            },
        );
    }

    public function type(): string
    {
        if ($this->nullable) {
            return '?'.Identifier::class;
        }

        return Identifier::class;
    }
}
