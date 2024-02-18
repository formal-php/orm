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
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Map,
};

/**
 * @internal
 * @template T of object
 * @implements RepositoryInterface<T>
 */
final class Repository implements RepositoryInterface
{
    private Transport $http;
    private Transaction $transaction;
    /** @var Definition<T> */
    private Definition $definition;
    private Encode $encode;
    /** @var Decode<T> */
    private Decode $decode;
    private Url $url;
    private Template $path;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(
        Transport $http,
        Transaction $transaction,
        Definition $definition,
        Url $url,
    ) {
        $this->http = $http;
        $this->transaction = $transaction;
        $this->definition = $definition;
        $this->encode = Encode::new();
        $this->decode = Decode::of($definition);
        $this->url = $url;
        $index = $definition->name();
        /** @psalm-suppress ArgumentTypeCoercion */
        $this->path = Template::of("/$index{/action,id}");
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
        Transaction $transaction,
        Definition $definition,
        Url $url,
    ): self {
        return new self($transport, $transaction, $definition, $url);
    }

    public function get(Aggregate\Id $id): Maybe
    {
        return ($this->http)(Request::of(
            $this->url->withPath(
                $this
                    ->path
                    ->expand(Map::of(
                        ['action', '_source'],
                        ['id', $id->value()],
                    ))
                    ->path(),
            ),
            Method::get,
            ProtocolVersion::v11,
        ))
            ->maybe()
            ->map(static fn($success) => $success->response()->body())
            ->flatMap(($this->decode)($id));
    }

    public function contains(Aggregate\Id $id): bool
    {
        return ($this->http)(Request::of(
            $this->url->withPath(
                $this
                    ->path
                    ->expand(Map::of(
                        ['action', '_doc'],
                        ['id', $id->value()],
                    ))
                    ->path(),
            ),
            Method::get,
            ProtocolVersion::v11,
        ))->match(
            static fn() => true,
            static fn() => false,
        );
    }

    public function add(Aggregate $data): void
    {
        $_ = ($this->http)(Request::of(
            $this->url->withPath(
                $this
                    ->path
                    ->expand(Map::of(
                        ['action', '_doc'],
                        ['id', $data->id()->value()],
                    ))
                    ->path(),
            ),
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
    }

    public function remove(Aggregate\Id $id): void
    {
    }

    public function fetch(
        ?Specification $specification,
        null|Sort\Property|Sort\Entity $sort,
        ?int $drop,
        ?int $take,
    ): Sequence {
        return Sequence::of();
    }

    public function size(Specification $specification = null): int
    {
        return 0;
    }

    public function any(Specification $specification = null): bool
    {
        return false;
    }
}
