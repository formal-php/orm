<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Effect;

/**
 * @internal
 */
final class Painless
{
    private function __construct()
    {
    }

    public function __invoke(Effect\Property $effect): array
    {
        $param = 'p'.\hash('xxh128', $effect->property());

        return [
            'lang' => 'painless',
            'source' => \sprintf(
                'ctx._source.%s = params.%s',
                $effect->property(),
                $param,
            ),
            'params' => [
                $param => $effect->value(),
            ],
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
