<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Effect;
use Innmind\Immutable\{
    Monoid\Concat,
    Str,
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
        $effect = $effect->unwrap();

        if ($effect instanceof Effect\Normalized\Entity) {
            return $this->entities($effect);
        }

        if ($effect instanceof Effect\Normalized\Child\Add) {
            return $this->addChildren($effect);
        }

        return $this->properties($effect);
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self;
    }

    private function properties(Effect\Normalized\Properties $effect): array
    {
        $effects = $effect->effects();

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
            'source' => $source->map(Str::of(...))->fold(new Concat)->toString(),
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

    private function entities(Effect\Normalized\Entity $effect): array
    {
        $property = $effect->property();
        $effects = $effect->effects();
        $params = $effects->map(static fn($effect) => [
            self::hash($property.$effect->property()),
            $effect->value(),
        ]);
        $source = $effects->map(static fn($effect) => \sprintf(
            'ctx._source.%s.%s = params.%s;',
            $property,
            $effect->property(),
            self::hash($property.$effect->property()),
        ));

        return [
            'lang' => 'painless',
            'source' => $source->map(Str::of(...))->fold(new Concat)->toString(),
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

    private function addChildren(Effect\Normalized\Child\Add $effect): array
    {
        $property = $effect->property();
        $entities = $effect->entities();
        $params = $entities
            ->indices()
            ->zip($entities)
            ->map(static fn($pair) => [
                self::hash($property.$pair[0]),
                \array_merge(
                    ...$pair[1]
                        ->properties()
                        ->map(static fn($property) => [$property->name() => $property->value()])
                        ->toList(),
                ),
            ]);
        $source = $entities->indices()->map(static fn($index) => \sprintf(
            'ctx._source.%s.add(params.%s);',
            $property,
            self::hash($property.$index),
        ));

        return [
            'lang' => 'painless',
            'source' => $source->map(Str::of(...))->fold(new Concat)->toString(),
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

    private static function hash(string $string): string
    {
        return 'p'.\hash('xxh128', $string);
    }
}
