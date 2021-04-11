<?php
declare(strict_types = 1);

namespace Formal\ORM\SQL;

use Formal\ORM\Definition\Property;
use Innmind\Immutable\Map;

final class Types
{
    /** @var Map<string, Type> */
    private Map $types;

    public function __construct(Type ...$types)
    {
        /** @var Map<string, Type> */
        $map = Map::of('string', Type::class);

        foreach ($types as $type) {
            $map = ($map)($type->type(), $type);
        }

        $this->types = $map;
    }

    public function __invoke(Property $property): Type
    {
        return $this->types->get(
            $property->type()->declaration(),
        );
    }

    /**
     * @return list<Type>
     */
    public static function default(): array
    {
        return [
            new Type\Id,
            new Type\Str,
            Type\Str::nullable(),
        ];
    }
}
