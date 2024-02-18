<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Innmind\HttpTransport\Transport;
use Innmind\Http\Request;
use Innmind\Url\Query;
use Innmind\Immutable\{
    Either,
    Str,
};

final class Refresh implements Transport
{
    private Transport $transport;

    private function __construct(Transport $transport)
    {
        $this->transport = $transport;
    }

    public function __invoke(Request $request): Either
    {
        if (
            !$request->method()->safe() &&
            Str::of($request->url()->path()->toString())->matches('~[a-zA-Z0-9]{8}(-[a-zA-Z0-9]{4}){3}-[a-zA-Z0-9]{12}$~')
        ) {
            $request = Request::of(
                $request->url()->withQuery(Query::of('refresh=true')),
                $request->method(),
                $request->protocolVersion(),
                $request->headers(),
                $request->body(),
            );
        }

        return ($this->transport)($request);
    }

    public static function of(Transport $transport): self
    {
        return new self($transport);
    }
}
