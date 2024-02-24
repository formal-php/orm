<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\Definition\Aggregates;
use Innmind\HttpTransport\Transport;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
};
use Innmind\Url\{
    Url,
    Path,
    Query,
};
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

final class DropIndex
{
    private Transport $http;
    private Aggregates $aggregates;
    private Url $url;

    private function __construct(
        Transport $http,
        Aggregates $aggregates,
        Url $url,
    ) {
        $this->http = $http;
        $this->aggregates = $aggregates;
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
            $this
                ->url
                ->withPath(Path::of('/'.$definition->name()))
                ->withQuery(Query::of('ignore_unavailable=true')),
            Method::delete,
            ProtocolVersion::v11,
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
