<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Effect;
use Innmind\Immutable\{
    Sequence,
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

    public function __invoke(Effect\Property|Effect\Collection $effect): array
    {
        if ($effect instanceof Effect\Property) {
            $effects = Sequence::of($effect);
        } else {
            $effects = $effect->effects();
        }

        $params = $effects->map(static fn($effect) => [
            'p'.\hash('xxh128', $effect->property()),
            $effect->value(),
        ]);
        $source = $effects->map(static fn($effect) => \sprintf(
            'ctx._source.%s = params.%s;',
            $effect->property(),
            'p'.\hash('xxh128', $effect->property()),
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

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self;
    }
}
