<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Formal\ORM\{
    Adapter\Repository as RepositoryInterface,
    Definition\Aggregate as Definition,
    Raw\Aggregate,
    Raw\Diff,
    Sort,
};
use Innmind\Filesystem\File\Content;
use Innmind\HttpTransport\Transport;
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
    Failure,
    Is,
    Shape,
    Of,
    Each,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Map,
    Validation,
};

/**
 * @internal
 * @template T of object
 * @implements RepositoryInterface<T>
 */
final class Repository implements RepositoryInterface
{
    private Transport $http;
    /** @var Definition<T> */
    private Definition $definition;
    private Encode $encode;
    /** @var Decode<T> */
    private Decode $decode;
    private Query $query;
    /** @var Constraint<mixed, 0|positive-int> */
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
        /**
         * @psalm-suppress MixedInferredReturnType
         * @psalm-suppress MixedArrayAccess
         * @psalm-suppress MixedReturnStatement
         * @var Constraint<mixed, 0|positive-int>
         */
        $this->pluckCount = Is::array()
            ->and(
                Shape::of(
                    'count',
                    Is::int()->and(Of::callable(static fn(int $value) => match (true) {
                        $value >= 0 => Validation::success($value),
                        default => Validation::fail(Failure::of('Count is negative')),
                    })),
                ),
            )
            ->map(static fn($body): int => $body['count']);
        /**
         * @psalm-suppress MixedInferredReturnType
         * @psalm-suppress MixedArrayAccess
         * @psalm-suppress MixedReturnStatement
         * @var Constraint<mixed, Sequence<array>>
         */
        $this->pluckHits = Is::array()
            ->and(Shape::of(
                'hits',
                Is::array()->and(Shape::of(
                    'hits',
                    Is::list()
                        ->and(Each::of(Shape::of(
                            '_source',
                            Is::array(),
                        )))
                        ->map(static fn($hits) => Sequence::of(...$hits)->map(
                            static fn($hit): array => $hit['_source'],
                        )),
                )),
            ))
            ->map(static fn($body): Sequence => $body['hits']['hits']);
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

    public function get(Aggregate\Id $id): Maybe
    {
        return ($this->http)(Request::of(
            $this->url('_source', $id->value()),
            Method::get,
            ProtocolVersion::v11,
        ))
            ->maybe()
            ->map(static fn($success) => $success->response()->body()->toString())
            ->map(Json::decode(...))
            ->flatMap(($this->decode)($id));
    }

    public function contains(Aggregate\Id $id): bool
    {
        return ($this->http)(Request::of(
            $this->url('_doc', $id->value()),
            Method::head,
            ProtocolVersion::v11,
        ))->match(
            static fn() => true,
            static fn() => false,
        );
    }

    public function add(Aggregate $data): void
    {
        $_ = ($this->http)(Request::of(
            $this->url('_doc', $data->id()->value()),
            Method::put,
            ProtocolVersion::v11,
            Headers::of(
                ContentType::of('application', 'json'),
            ),
            ($this->encode)($data),
        ))->match(
            static fn() => null,
            static fn() => throw new \RuntimeException('Unable to persist the aggregate'),
        );
    }

    public function update(Diff $data): void
    {
        $_ = ($this->http)(Request::of(
            $this->url('_update', $data->id()->value()),
            Method::post,
            ProtocolVersion::v11,
            Headers::of(
                ContentType::of('application', 'json'),
            ),
            ($this->encode)($data),
        ))->match(
            static fn() => null,
            static fn() => throw new \RuntimeException('Unable to update the aggregate'),
        );
    }

    public function remove(Aggregate\Id $id): void
    {
        $_ = ($this->http)(Request::of(
            $this->url('_doc', $id->value()),
            Method::delete,
            ProtocolVersion::v11,
        ))->match(
            static fn() => null,
            static fn() => null,
        );
    }

    public function removeAll(Specification $specification): void
    {
        $_ = ($this->http)(Request::of(
            $this->url('_delete_by_query'),
            Method::post,
            ProtocolVersion::v11,
            Headers::of(
                ContentType::of('application', 'json'),
            ),
            Content::ofString(Json::encode([
                'query' => ($this->query)($specification),
            ])),
        ))->match(
            static fn() => null,
            static fn() => throw new \RuntimeException('Unable to remove multiple aggregates'),
        );
    }

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

        return $this->search($drop ?? 0, $take, $normalizedSort, $query);
    }

    public function size(Specification $specification = null): int
    {
        $content = null;

        if ($specification) {
            $query = ($this->query)($specification);
            $content = ['query' => $query];
        }

        return ($this->http)(Request::of(
            $this->url('_count'),
            match ($content) {
                null => Method::get,
                default => Method::post,
            },
            ProtocolVersion::v11,
            match ($content) {
                null => null,
                default => Headers::of(
                    ContentType::of('application', 'json'),
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

    public function any(Specification $specification = null): bool
    {
        return !$this
            ->fetch($specification, null, null, 1)
            ->empty();
    }

    /**
     * @param non-empty-string $action
     * @param non-empty-string|null $id
     */
    private function url(string $action, string $id = null): Url
    {
        /** @var Map<non-empty-string, non-empty-string> */
        $map = Map::of(['action', $action]);

        return $this->url->withPath(
            $this
                ->path
                ->expand(match ($id) {
                    null => $map,
                    default => ($map)('id', $id),
                })
                ->path(),
        );
    }

    /**
     * @param 0|positive-int $drop
     * @return Sequence<Aggregate>
     */
    private function stream(int $drop, ?array $sort, ?array $query): Sequence
    {
        return Sequence::lazy(function() use ($drop, $sort, $query) {
            // This loop will break when reaching 10k documents (see
            // self::search()). The user SHOULD be aware of this limitation
            // after reading the documentation.
            // In the case the user is not aware of this limitation, streaming
            // more than 10k documents will crash the app (thus making the user
            // aware of this limitation).
            while (true) {
                $hits = $this->search($drop, 100, $sort, $query);

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
     * @param 0|positive-int $drop
     * @param positive-int $take
     *
     * @return Sequence<Aggregate>
     */
    private function search(
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

        $decode = ($this->decode)();

        return ($this->http)(Request::of(
            $this->url('_search'),
            Method::post,
            ProtocolVersion::v11,
            Headers::of(
                ContentType::of('application', 'json'),
            ),
            Content::ofString(Json::encode($payload)),
        ))
            ->map(static fn($success) => $success->response()->body()->toString())
            ->map(Json::decode(...))
            ->maybe()
            ->flatMap(fn($content) => ($this->pluckHits)($content)->maybe())
            ->toSequence()
            ->flatMap(static fn($hits) => $hits)
            ->flatMap(static fn($hit) => $decode($hit)->toSequence());
    }
}
