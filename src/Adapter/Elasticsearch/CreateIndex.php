<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Definition\Aggregates;
use Innmind\HttpTransport\Transport;
use Innmind\Filesystem\File\Content;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
    Headers,
    Header\ContentType,
};
use Innmind\Url\{
    Url,
    Path,
};
use Innmind\Json\Json;
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

final class CreateIndex
{
    private Transport $http;
    private Aggregates $aggregates;
    private Mapping $mapping;
    private Url $url;

    private function __construct(
        Transport $http,
        Aggregates $aggregates,
        Url $url,
    ) {
        $this->http = $http;
        $this->aggregates = $aggregates;
        $this->mapping = Mapping::new();
        $this->url = $url;
    }

    /**
     * @param class-string $class
     *
     * @return Maybe<SideEffect>
     */
    public function __invoke(string $class): Maybe
    {
        $definition = $this->aggregates->get($class);

        return ($this->http)(Request::of(
            $this->url->withPath(
                Path::of('/'.$definition->name()),
            ),
            Method::put,
            ProtocolVersion::v11,
            Headers::of(
                ContentType::of('application', 'json'),
            ),
            Content::ofString(Json::encode([
                'mappings' => ($this->mapping)($definition),
            ])),
        ))
            ->maybe()
            ->map(static fn() => new SideEffect);
    }

    public static function of(
        Transport $transport,
        Aggregates $aggregates,
        Url $url,
    ): self {
        return new self($transport, $aggregates, $url);
    }
}
