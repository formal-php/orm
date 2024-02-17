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
use Innmind\UrlTemplate\Template;
use Innmind\Url\Url;
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

/**
 * @internal
 * @template T of object
 * @implements RepositoryInterface<T>
 */
final class Repository implements RepositoryInterface
{
    private Transport $transport;
    private Transaction $transaction;
    /** @var Definition<T> */
    private Definition $definition;
    private Url $url;
    private Template $template;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(
        Transport $transport,
        Transaction $transaction,
        Definition $definition,
        Url $url,
    ) {
        $this->transport = $transport;
        $this->transaction = $transaction;
        $this->definition = $definition;
        $this->url = $url;
        $index = $definition->name();
        /** @psalm-suppress ArgumentTypeCoercion */
        $this->template = Template::of("/$index{/action,id}");
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
        /** @var Maybe<Aggregate> */
        return Maybe::nothing();
    }

    public function contains(Aggregate\Id $id): bool
    {
        return false;
    }

    public function add(Aggregate $data): void
    {
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
