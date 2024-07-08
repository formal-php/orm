# Export aggregates as a CSV

Since Formal sits on top of the [Innmind ecosystem](https://innmind.github.io/documentation/) this pretty simple.

```php
use Innmind\OperatingSystem\Factory;
use Innmind\Filesystem\{
    File,
    File\Content\Line,
};
use Innmind\Url\Path;
use Innmind\Immutable\Str;

$os = Factory::build();
$lines = $orm
    ->repository(User::class)
    ->all()
    ->map(static fn(User $user): string => $user->name()->toString())
    ->map(Str::of(...))
    ->map(Line::of(...));
$file = File::named(
    'users.csv',
    File\Content::ofLines($lines),
);
$os
    ->filesystem()
    ->mount(Path::of('somewhere'))
    ->add($file);
```

This create a `users.csv` file where each line contains the name of a user.

!!! success ""
    Since everything is lazy by default you can generate files of any size.

You can learn more about handling files [here](https://innmind.github.io/documentation/getting-started/operating-system/filesystem/).
