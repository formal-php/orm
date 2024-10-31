<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Fixtures\Formal\ORM\CreatedAt;
use Formal\ORM\Adapter\Elasticsearch\ElasticsearchType;
use Formal\ORM\Adapter\SQL\SQLType;
use Formal\ORM\Definition\{
    Type,
    Types,
};
use Formal\AccessLayer\Table\Column\Type as Definition;
use Innmind\Type\{
    Type as Concrete,
    ClassName,
};
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 * @implements Type<CreatedAt>
 */
final class CreatedAtType implements Type, SQLType, ElasticsearchType
{
    private function __construct()
    {
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function of(Types $types, Concrete $type): Maybe
    {
        return Maybe::just($type)
            ->filter(static fn($type) => $type->accepts(ClassName::of(CreatedAt::class)))
            ->map(static fn() => new self);
    }

    public function elasticsearchType(): array
    {
        return ['type' => 'double'];
    }

    public function sqlType(): Definition
    {
        return Definition::decimal(65, 2);
    }

    public function normalize(mixed $value): null|string|int|float|bool
    {
        return $value->toFloat();
    }

    public function denormalize(null|string|int|float|bool $value): mixed
    {
        if (!\is_float($value) && !\is_int($value)) {
            throw new \LogicException("'$value' is not a float");
        }

        return new CreatedAt($value);
    }
}
