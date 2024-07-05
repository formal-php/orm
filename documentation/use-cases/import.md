# Import aggregates from a CSV

## In a single transaction

```php
use Innmind\OperatingSystem\Factory;
use Innmind\Filesystem\{
    File,
    Name as FileName,
    File\Content\Line,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    Predicate\Instance,
};

$os = Factory::of();
$repository = $orm->repository(User::class);
$orm->transactional(
    static function() use ($os, $repository) {
        $_ = $os
            ->filesystem()
            ->mount(Path::of('somewhere'))
            ->get(FileName::of('users.csv'))
            ->keep(Instance::of(File::class))
            ->toSequence()
            ->flatMap(static fn(File $users) => $users->content()->lines())
            ->map(static fn(Line $line) => User::new(Name::of(
                $line->toString(), //(1)
            )))
            ->foreach($repository->put(...));

        return Either::right(null);
    },
);
```

1. Ths line never contains the `\n` character, so you don't have to handle it yourself.

## Commit the transaction every 100 users

```php
use Innmind\OperatingSystem\Factory;
use Innmind\Filesystem\{
    File,
    Name as FileName,
    File\Content\Line,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    Sequence,
    Predicate\Instance,
};

$os = Factory::of();
$repository = $orm->repository(User::class);

$_ = $os
    ->filesystem()
    ->mount(Path::of('somewhere'))
    ->get(FileName::of('users.csv'))
    ->keep(Instance::of(File::class))
    ->toSequence()
    ->flatMap(static fn(File $users) => $users->content()->lines())
    ->map(static fn(Line $line) => User::new(Name::of(
        $line->toString(),
    )))
    ->chunk(100)
    ->foreach(
        static fn(Sequence $users) => $orm->transactional(
            static function() use ($users, $repository) {
                $users->foreach($repository->put(...));

                return Either::right(null);
            },
        ),
    );
```
