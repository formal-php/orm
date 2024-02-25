<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Fixtures\Formal\ORM\Sortable;
use Formal\ORM\Adapter\Elasticsearch\ElasticsearchType;
use Formal\ORM\Definition\{
    Type,
    Types,
};
use Innmind\Type\{
    Type as Concrete,
    ClassName,
};
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 * @implements Type<Sortable>
 */
final class SortableType implements Type, ElasticsearchType
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
            ->filter(static fn($type) => $type->accepts(ClassName::of(Sortable::class)))
            ->map(static fn() => new self);
    }

    public function elasticsearchType(): array
    {
        return ['type' => 'keyword'];
    }

    public function normalize(mixed $value): null|string|int|bool
    {
        return $value->toString();
    }

    public function denormalize(null|string|int|bool $value): mixed
    {
        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        return new Sortable($value);
    }
}
