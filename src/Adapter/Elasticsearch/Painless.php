<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\{
    Effect,
    Raw\Aggregate\Collection\Entity as RawEntity,
    Specification,
};
use Innmind\Specification\Sign;
use Innmind\Immutable\{
    Monoid\Concat,
    Str,
    Sequence,
};

/**
 * @internal
 */
final class Painless
{
    private function __construct()
    {
    }

    public function __invoke(Effect\Normalized $effect): array
    {
        return $effect->match(
            $this->properties(...),
            $this->entities(...),
            $this->optional(...),
            $this->optionalNothing(...),
            $this->addChildren(...),
            $this->removeChildren(...),
        );
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self;
    }

    /**
     * @param Sequence<Effect\Normalized\Property> $effects
     */
    private function properties(Sequence $effects): array
    {
        $params = $effects->map(static fn($effect) => [
            self::hash($effect->property()),
            $effect->value(),
        ]);
        $source = $effects->map(static fn($effect) => \sprintf(
            'ctx._source.%s = params.%s;',
            $effect->property(),
            self::hash($effect->property()),
        ));

        return [
            'lang' => 'painless',
            'source' => $source->map(Str::of(...))->fold(Concat::monoid)->toString(),
            'params' => $params->reduce(
                [],
                static function(array $params, $param) {
                    /** @psalm-suppress MixedAssignment */
                    $params[$param[0]] = $param[1];

                    return $params;
                },
            ),
        ];
    }

    /**
     * @param non-empty-string $entity
     * @param Sequence<Effect\Normalized\Property> $effects
     */
    private function entities(string $entity, Sequence $effects): array
    {
        $params = $effects->map(static fn($effect) => [
            self::hash($entity.$effect->property()),
            $effect->value(),
        ]);
        $source = $effects->map(static fn($effect) => \sprintf(
            'ctx._source.%s.%s = params.%s;',
            $entity,
            $effect->property(),
            self::hash($entity.$effect->property()),
        ));

        return [
            'lang' => 'painless',
            'source' => $source->map(Str::of(...))->fold(Concat::monoid)->toString(),
            'params' => $params->reduce(
                [],
                static function(array $params, $param) {
                    /** @psalm-suppress MixedAssignment */
                    $params[$param[0]] = $param[1];

                    return $params;
                },
            ),
        ];
    }

    /**
     * @param non-empty-string $optional
     * @param Sequence<Effect\Normalized\Property> $effects
     */
    private function optional(string $optional, Sequence $effects): array
    {
        $params = $effects->map(static fn($effect) => [
            self::hash($optional.$effect->property()),
            $effect->value(),
        ]);
        $source = $effects->map(static fn($effect) => \sprintf(
            'ctx._source.%s.%s = params.%s;',
            $optional,
            $effect->property(),
            self::hash($optional.$effect->property()),
        ));

        return [
            'lang' => 'painless',
            'source' => $source
                ->prepend(Sequence::of(
                    \sprintf('if (ctx._source.%s == null) {', $optional),
                    '    return;',
                    '}',
                ))
                ->map(Str::of(...))
                ->fold(Concat::monoid)
                ->toString(),
            'params' => $params->reduce(
                [],
                static function(array $params, $param) {
                    /** @psalm-suppress MixedAssignment */
                    $params[$param[0]] = $param[1];

                    return $params;
                },
            ),
        ];
    }

    /**
     * @param non-empty-string $optional
     */
    private function optionalNothing(string $optional): array
    {
        return [
            'lang' => 'painless',
            'source' => \sprintf(
                'ctx._source.%s = null;',
                $optional,
            ),
        ];
    }

    /**
     * @param non-empty-string $collection
     * @param Sequence<RawEntity> $entities
     */
    private function addChildren(string $collection, Sequence $entities): array
    {
        $params = $entities
            ->indices()
            ->zip($entities)
            ->map(static fn($pair) => [
                self::hash($collection.$pair[0]),
                \array_merge(
                    ...$pair[1]
                        ->properties()
                        ->map(static fn($property) => [$property->name() => $property->value()])
                        ->toList(),
                ),
            ]);
        $source = $entities->indices()->map(static fn($index) => \sprintf(
            'ctx._source.%s.add(params.%s);',
            $collection,
            self::hash($collection.$index),
        ));

        return [
            'lang' => 'painless',
            'source' => $source->map(Str::of(...))->fold(Concat::monoid)->toString(),
            'params' => $params->reduce(
                [],
                static function(array $params, $param) {
                    /** @psalm-suppress MixedAssignment */
                    $params[$param[0]] = $param[1];

                    return $params;
                },
            ),
        ];
    }

    /**
     * @param non-empty-string $collection
     */
    private function removeChildren(
        string $collection,
        Specification\Property $comparator,
    ): array {
        $param = self::hash($collection);
        $condition = match ($comparator->sign()) {
            Sign::equality => "entity.%s == params.$param",
            Sign::lessThan => "entity.%s < params.$param",
            Sign::moreThan => "entity.%s > params.$param",
            Sign::startsWith => "entity.%s.startsWith(params.$param)",
            Sign::endsWith => "entity.%s.endsWith(params.$param)",
            Sign::contains => "entity.%s.contains(params.$param)",
            Sign::in => "params.$param.stream().anyMatch(v -> v == entity.%s)",
        };
        $comparison = \sprintf(
            $condition,
            $comparator->property(),
        );
        $source = <<<SOURCE
        ctx._source.$collection = ctx
            ._source
            .$collection
            .stream()
            .filter(entity -> !($comparison))
            .collect(Collectors.toList());
        SOURCE;

        return [
            'lang' => 'painless',
            'source' => $source,
            'params' => [
                self::hash($collection) => $comparator->value(),
            ],
        ];
    }

    private static function hash(string $string): string
    {
        return 'p'.\hash('xxh128', $string);
    }
}
