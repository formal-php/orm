<?php
declare(strict_types = 1);

namespace Formal\ORM\Adapter\Elasticsearch;

use Innmind\HttpTransport\Transport;
use Innmind\Http\Request;
use Innmind\Url\Query;
use Innmind\Immutable\Str;

final class Refresh
{
    public static function of(Transport $transport): Transport
    {
        /** @psalm-suppress InternalMethod */
        return Transport::via(static function($request) use ($transport) {
            $path = Str::of($request->url()->path()->toString());

            if (
                !$request->method()->safe() &&
                (
                    $path->matches('~[a-zA-Z0-9]{8}(-[a-zA-Z0-9]{4}){3}-[a-zA-Z0-9]{12}$~') ||
                    $path->endsWith('_delete_by_query') ||
                    $path->endsWith('_update_by_query')
                )
            ) {
                $request = Request::of(
                    $request->url()->withQuery(Query::of('refresh=true')),
                    $request->method(),
                    $request->protocolVersion(),
                    $request->headers(),
                    $request->body(),
                );
            }

            return $transport($request);
        });
    }
}
