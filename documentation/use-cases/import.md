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
    SideEffect,
    Predicate\Instance,
};

$os = Factory::of();
$repository = $orm->repository(User::class);
$orm->transactional(
    static fn() => $os
        ->filesystem()
        ->mount(Path::of('somewhere'))
        ->maybe()
        ->flatMap(static fn($adapter) => $adapter->get(FileName::of('users.csv')))
        ->keep(Instance::of(File::class))
        ->toSequence()
        ->flatMap(static fn(File $users) => $users->content()->lines())
        ->map(static fn(Line $line) => User::new(Name::of(
            $line->toString(), //(1)
        )))
        ->sink(SideEffect::identity())
        ->attempt(static fn($_, User $user) => $repository->put($user))
        ->either(),
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
    SideEffect,
    Sequence,
    Predicate\Instance,
};

$os = Factory::of();
$repository = $orm->repository(User::class);

$_ = $os
    ->filesystem()
    ->mount(Path::of('somewhere'))
    ->maybe()
    ->flatMap(static fn($adapter) => $adapter->get(FileName::of('users.csv')))
    ->keep(Instance::of(File::class))
    ->toSequence()
    ->flatMap(static fn(File $users) => $users->content()->lines())
    ->map(static fn(Line $line) => User::new(Name::of(
        $line->toString(),
    )))
    ->chunk(100)
    ->foreach(
        static fn(Sequence $users) => $orm->transactional(
            static fn() => $users
                ->sink(SideEffect::identity())
                ->attempt(static fn($_, User $user) => $repository->put($user))
                ->either(),
        ),
    );
```
