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
    private function __construct(
        private Transport $transport,
        private Url $url,
        private Elasticsearch\Transaction $transaction,
    ) {
    }

    public static function of(Transport $transport, ?Url $url = null): self
    {
        return new self(
            $transport,
            $url ?? Url::of('http://localhost:9200/'),
            Elasticsearch\Transaction::of(),
        );
    }

    #[\Override]
    public function repository(Aggregate $definition): Repository
    {
        return Elasticsearch\Repository::of(
            $this->transport,
            $definition,
            $this->url,
        );
    }

    #[\Override]
    public function transaction(): Transaction
    {
        return $this->transaction;
    }
}
