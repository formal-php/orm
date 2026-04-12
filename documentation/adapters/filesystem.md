# Filesystem

This is the adapter you've been using since the start of this documentation.

You can use any implementation of the `Innmind\Filesystem\Adapter` interface provided by [`innmind/filesystem`](https://packagist.org/packages/innmind/filesystem).

## In memory

It allows to quickly iterate on some code and see if it works, and move later on on a more persistent storage.

This adapter is also useful when testing an application. By using an in memory storage your tests can run faster as it doesn't have to persist anything to the filesystem.

## Persistent

You can persist your aggregates to the filesystem via:

```php
use Formal\ORM\Manager;
use Innmind\Filesystem\Adapter;
use Innmind\Url\Path;

$orm = Manager::filesystem(Adapter::mount(Path::of('somewhere/'))->unwrap());
```

You should use this storage for proof of concept kind of apps. Or for small CLI apps you use locally.

!!! warning ""
    **DO NOT** use this storage for a production app. As this will be very slow and not concurrent safe.
