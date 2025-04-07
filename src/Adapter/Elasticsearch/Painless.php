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

    public function __invoke(Effect\Property\Collection|Effect\Entity $effect): array
    {
        if ($effect instanceof Effect\Entity) {
            return $this->entities($effect);
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

    private function properties(Effect\Property\Collection $effect): array
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

    private function entities(Effect\Entity $effect): array
    {
        $property = $effect->property();
        $effects = $effect->effects()->effects();
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

    private static function hash(string $string): string
    {
        return 'p'.\hash('xxh128', $string);
    }
}
