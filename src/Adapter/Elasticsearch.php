<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter;

use Formal\ORM\{
    Adapter,
    Definition\Aggregate,
};
use Innmind\HttpTransport\Transport;
use Innmind\Url\Url;

final class Elasticsearch implements Adapter
{
    private Transport $transport;
    private Url $url;
    private Elasticsearch\Transaction $transaction;

    private function __construct(Transport $transport, Url $url)
    {
        $this->transport = $transport;
        $this->url = $url;
        $this->transaction = Elasticsearch\Transaction::of();
    }

    public static function of(Transport $transport, Url $url = null): self
    {
        return new self($transport, $url ?? Url::of('http://localhost:9200/'));
    }

    public function repository(Aggregate $definition): Repository
    {
        return Elasticsearch\Repository::of(
            $this->transport,
            $this->transaction,
            $definition,
            $this->url,
        );
    }

    public function transaction(): Transaction
    {
        return $this->transaction;
    }
}
