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
use Innmind\Filesystem\Adapter\Filesystem;
use Innmind\Url\Path;

$orm = Manager::filesystem(Filesystem::mount(Path::of('somewhere/')));
```

You should use this storage for proof of concept kind of apps. Or for small CLI apps you use locally.

!!! warning ""
    **DO NOT** use this storage for a production app. As this will be very slow and not concurrent safe.

## S3

The package [`innmind/s3`](https://packagist.org/packages/innmind/s3) exposes a filesystem adapter. You could use it like this:

```php
use Formal\ORM\Manager;
use Innmind\OperatingSystem\Factory as OSFactory;
use Innmind\S3\{
    Factory,
    Region,
    Filesystem\Adapter,
};
use Innmind\Url\Url;

$os = OSFactory::build(); //(1)
$bucket = Factory::of($os)->build(
    Url::of('https://acces_key:acces_secret@bucket-name.s3.region-name.scw.cloud/'),
    Region::of('region-name'),
);

$orm = Manager::filesystem(
    Adapter::of($bucket),
);
```

1. See [`innmind/operating-system`](https://innmind.github.io/documentation/getting-started/operating-system/)

You should use this storage for proof of concept kind of apps. Or for small CLI apps and you want the storage to be available across multiple computers.

!!! warning ""
    **DO NOT** use this storage for a production app. As this will be very slow (due to network latency) and not concurrent safe.
