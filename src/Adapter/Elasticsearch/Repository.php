<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\{
    Adapter\Repository as RepositoryInterface,
    Adapter\Repository\Effectful,
    Definition\Aggregate as Definition,
    Raw\Aggregate,
    Raw\Diff,
    Sort,
    Effect,
};
use Innmind\Filesystem\File\Content;
use Innmind\HttpTransport\Transport;
use Innmind\MediaType\MediaType;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
    Headers,
    Header\ContentType,
};
use Innmind\UrlTemplate\Template;
use Innmind\Url\Url;
use Innmind\Json\Json;
use Innmind\Validation\{
    Constraint,
    Is,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Map,
    Attempt,
    SideEffect,
};

/**
 * @internal
 * @template T of object
 * @implements RepositoryInterface<T>
 */
final class Repository implements RepositoryInterface, Effectful
{
    private Transport $http;
    /** @var Definition<T> */
    private Definition $definition;
    private Encode $encode;
    /** @var Decode<T> */
    private Decode $decode;
    private Painless $script;
    private Query $query;
    /** @var Constraint<mixed, int<0, max>> */
    private Constraint $pluckCount;
    /** @var Constraint<mixed, Sequence<array>> */
    private Constraint $pluckHits;
    private Url $url;
    private Template $path;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(
        Transport $http,
        Definition $definition,
        Url $url,
    ) {
        $this->http = $http;
        $this->definition = $definition;
        $this->encode = Encode::new();
        $this->decode = Decode::of($definition);
        $this->query = Query::new(Mapping::new(), $definition);
        $this->script = Painless::new();
        /**
         * @psalm-suppress MixedReturnStatement
         * @var Constraint<mixed, int<0, max>>
         */
        $this->pluckCount = Is::shape(
            'count',
            Is::int()->positive()->or(Is::value(0)),
        )->map(static fn($body): int => $body['count']);
        /**
         * @psalm-suppress MixedArrayAccess
         * @psalm-suppress MixedReturnStatement
         * @var Constraint<mixed, Sequence<array>>
         */
        $this->pluckHits = Is::shape(
            'hits',
            Is::shape(
                'hits',
                Is::list(Is::shape('_source', Is::array()))
                    ->map(static fn($hits) => Sequence::of(...$hits)->map(
                        static fn($hit): array => $hit['_source'],
                    )),
            ),
        )->map(static fn($body): Sequence => $body['hits']['hits']);
        $this->url = $url;
        $index = $definition->name();
        /** @psalm-suppress ArgumentTypeCoercion */
        $this->path = Template::of("/$index{/action}{/id}");
    }

    /**
     * @internal
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(
        Transport $transport,
        Definition $definition,
        Url $url,
    ): self {
        return new self($transport, $definition, $url);
    }

    #[\Override]
    public function get(Aggregate\Id $id): Maybe
    {
        return ($this->http)(Request::of(
            self::url(
                $this->url,
                $this->path,
                '_source',
                $id->value(),
            ),
            Method::get,
            ProtocolVersion::v11,
        ))
            ->maybe()
            ->map(static fn($success) => $success->response()->body()->toString())
            ->map(Json::decode(...))
            ->flatMap(($this->decode)($id));
    }

    #[\Override]
    public function contains(Aggregate\Id $id): bool
    {
        return ($this->http)(Request::of(
            self::url(
                $this->url,
                $this->path,
                '_doc',
                $id->value(),
            ),
            Method::head,
            ProtocolVersion::v11,
        ))->match(
            static fn() => true,
            static fn() => false,
        );
    }

    #[\Override]
    public function add(Aggregate $data): Attempt
    {
        return ($this->http)(Request::of(
            self::url(
                $this->url,
                $this->path,
                '_doc',
                $data->id()->value(),
            ),
            Method::put,
            ProtocolVersion::v11,
            Headers::of(
                ContentType::of(new MediaType('application', 'json')),
            ),
            ($this->encode)($data),
        ))
            ->attempt(
                static fn() => new \RuntimeException('Unable to persist the aggregate'),
            )
            ->map(static fn() => SideEffect::identity());
    }

    #[\Override]
    public function update(Diff $data): Attempt
    {
        return ($this->http)(Request::of(
            self::url(
                $this->url,
                $this->path,
                '_update',
                $data->id()->value(),
            ),
            Method::post,
            ProtocolVersion::v11,
            Headers::of(
                ContentType::of(new MediaType('application', 'json')),
            ),
            ($this->encode)($data),
        ))
            ->attempt(
                static fn() => new \RuntimeException('Unable to update the aggregate'),
            )
            ->map(static fn() => SideEffect::identity());
    }

    #[\Override]
    public function effect(
        Effect\Normalized $effect,
        ?Specification $specification,
    ): Attempt {
        $payload = [
            'script' => ($this->script)($effect),
        ];

        if ($specification) {
            $payload['query'] = ($this->query)($specification);
        }

        return ($this->http)(Request::of(
            self::url(
                $this->url,
                $this->path,
                '_update_by_query',
            ),
            Method::post,
            ProtocolVersion::v11,
            Headers::of(
                ContentType::of(new MediaType('application', 'json')),
            ),
            Content::ofString(Json::encode($payload)),
        ))
            ->attempt(
                static fn() => new \RuntimeException('Unable to update multiple aggregates'),
            )
            ->map(static fn() => SideEffect::identity());
    }

    #[\Override]
    public function remove(Aggregate\Id $id): Attempt
    {
        return ($this->http)(Request::of(
            self::url(
                $this->url,
                $this->path,
                '_doc',
                $id->value(),
            ),
            Method::delete,
            ProtocolVersion::v11,
        ))
            ->attempt(
                static fn() => new \RuntimeException('Failed to remove aggregate'),
            )
            ->recover(static fn() => Attempt::result(SideEffect::identity()))
            ->map(static fn() => SideEffect::identity());
    }

    #[\Override]
    public function removeAll(Specification $specification): Attempt
    {
        return ($this->http)(Request::of(
            self::url(
                $this->url,
                $this->path,
                '_delete_by_query',
            ),
            Method::post,
            ProtocolVersion::v11,
            Headers::of(
                ContentType::of(new MediaType('application', 'json')),
            ),
            Content::ofString(Json::encode([
                'query' => ($this->query)($specification),
            ])),
        ))
            ->attempt(
                static fn() => new \RuntimeException('Unable to remove multiple aggregates'),
            )
            ->map(static fn() => SideEffect::identity());
    }

    #[\Override]
    public function fetch(
        ?Specification $specification,
        null|Sort\Property|Sort\Entity $sort,
        ?int $drop,
        ?int $take,
    ): Sequence {
        $normalizedSort = null;
        $query = null;

        // When no sorting is defined we sort by id to make sure ES doesn't
        // return the same document twice. This is not applied when there's a
        // specification in order to not alter the scoring.
        if (\is_null($specification) && \is_null($sort)) {
            $normalizedSort = [[
                $this->definition->id()->property() => 'asc',
            ]];
        }

        if ($sort instanceof Sort\Property) {
            $normalizedSort = [[
                $sort->name() => $sort->direction()->name,
            ]];
        }

        if ($sort instanceof Sort\Entity) {
            $normalizedSort = [[
                $sort->name().'.'.$sort->property()->name() => $sort->direction()->name,
            ]];
        }

        if ($specification) {
            $query = ($this->query)($specification);
        }

        if (\is_null($take)) {
            return $this->stream($drop ?? 0, $normalizedSort, $query);
        }

        $decode = ($this->decode)();

        return self::search(
            $this->http,
            $decode,
            $this->pluckHits,
            $this->url,
            $this->path,
            $drop ?? 0,
            $take,
            $normalizedSort,
            $query,
        );
    }

    #[\Override]
    public function size(?Specification $specification = null): int
    {
        $content = null;

        if ($specification) {
            $query = ($this->query)($specification);
            $content = ['query' => $query];
        }

        return ($this->http)(Request::of(
            self::url(
                $this->url,
                $this->path,
                '_count',
            ),
            match ($content) {
                null => Method::get,
                default => Method::post,
            },
            ProtocolVersion::v11,
            match ($content) {
                null => null,
                default => Headers::of(
                    ContentType::of(new MediaType('application', 'json')),
                ),
            },
            match ($content) {
                null => null,
                default => Content::ofString(Json::encode($content)),
            },
        ))
            ->maybe()
            ->map(static fn($success) => $success->response()->body()->toString())
            ->map(Json::decode(...))
            ->flatMap(fn($content) => ($this->pluckCount)($content)->maybe())
            ->match(
                static fn($count) => $count,
                static fn() => throw new \RuntimeException('Count is negative'),
            );
    }

    #[\Override]
    public function any(?Specification $specification = null): bool
    {
        return !$this
            ->fetch($specification, null, null, 1)
            ->empty();
    }

    /**
     * @param non-empty-string $action
     * @param non-empty-string|null $id
     */
    private static function url(
        Url $url,
        Template $path,
        string $action,
        ?string $id = null,
    ): Url {
        /** @var Map<non-empty-string, non-empty-string> */
        $map = Map::of(['action', $action]);

        return $url->withPath(
            $path
                ->expand(match ($id) {
                    null => $map,
                    default => ($map)('id', $id),
                })
                ->path(),
        );
    }

    /**
     * @param int<0, max> $drop
     * @return Sequence<Aggregate>
     */
    private function stream(int $drop, ?array $sort, ?array $query): Sequence
    {
        $http = $this->http;
        $decode = ($this->decode)();
        $pluckHits = $this->pluckHits;
        $url = $this->url;
        $path = $this->path;

        return Sequence::lazy(static function() use (
            $http,
            $decode,
            $pluckHits,
            $url,
            $path,
            $drop,
            $sort,
            $query,
        ) {
            // This loop will break when reaching 10k documents (see
            // self::search()). The user SHOULD be aware of this limitation
            // after reading the documentation.
            // In the case the user is not aware of this limitation, streaming
            // more than 10k documents will crash the app (thus making the user
            // aware of this limitation).
            while (true) {
                $hits = self::search(
                    $http,
                    $decode,
                    $pluckHits,
                    $url,
                    $path,
                    $drop,
                    100,
                    $sort,
                    $query,
                );

                yield $hits;

                // No need to do more calls as ES returns less than we asked for.
                // This call is safe as the Sequence returned by self::search()
                // is memoized.
                if ($hits->size() < 100) {
                    return;
                }

                $drop += 100;
            }
        })->flatMap(static fn($aggregates) => $aggregates);
    }

    /**
     * @param callable(mixed): Maybe<Aggregate> $decode
     * @param Constraint<mixed, Sequence<array>> $pluckHits
     * @param int<0, max> $drop
     * @param int<1, max> $take
     *
     * @return Sequence<Aggregate>
     */
    private static function search(
        Transport $http,
        callable $decode,
        Constraint $pluckHits,
        Url $url,
        Template $path,
        int $drop,
        int $take,
        ?array $sort,
        ?array $query,
    ): Sequence {
        if (($drop + $take) > 10_000) {
            throw new \LogicException('Elasticsearch does not support listing more than 10k documents');
        }

        $payload = ['size' => $take];

        if ($drop !== 0) {
            $payload['from'] = $drop;
        }

        if (\is_array($sort)) {
            $payload['sort'] = $sort;
        }

        if (\is_array($query)) {
            $payload['query'] = $query;
        }

        return $http(Request::of(
            self::url(
                $url,
                $path,
                '_search',
            ),
            Method::post,
            ProtocolVersion::v11,
            Headers::of(
                ContentType::of(new MediaType('application', 'json')),
            ),
            Content::ofString(Json::encode($payload)),
        ))
            ->map(static fn($success) => $success->response()->body()->toString())
            ->map(Json::decode(...))
            ->maybe()
            ->flatMap(static fn($content) => $pluckHits($content)->maybe())
            ->toSequence()
            ->flatMap(static fn($hits) => $hits)
            ->flatMap(static fn($hit) => $decode($hit)->toSequence());
    }
}
