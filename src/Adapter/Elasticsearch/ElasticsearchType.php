<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

/**
 * @psalm-immutable
 */
interface ElasticsearchType
{
    /**
     * @return array{
     *     type: string,
     *     index?: bool,
     * }
     */
    public function elasticsearchType(): array;
}
