# Going to production

In all examples of this documentation we use the filesystem adapters. While this is fine for tests and building prototypes this is not production safe as it doesn't support concurrent calls.

For production you should use the SQL adapter.

For this adapter to work you need to do a few things:

## Creating the tables

```php
use Formal\ORM\{
    Definition\Aggregates,
    Definition\Types,
    Adapter\SQL\ShowCreateTable,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;

$os = Factory::build();
$aggregates = Aggregates::of(Types::default());
$show = ShowCreateTable::of($aggregates);
$connection = $os->remote()->sql(Url::of('mysql://user:password@host:3306/database?charset=utf8mb4'));

$_ = $show(User::class)->foreach($connection);
```

If you run this code it will automatically create the tables necessary to store your users. A better approach would be to copy the queries returned by `$show(User::class)` to a migration system.

If you have defined type converters they should also implement `Formal\ORM\Adapter\SQL\SQLType` so the above code will know the correct sql type to use for your property.

## Switching the ORM adapter

```php
use Formal\ORM\{
    Manager,
    Adapter,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;

$os = Factory::build();
$manager = Manager::of(
    Adapter\SQL::of($os->remote()->sql(Url::of('mysql://user:password@host:3306/database?charset=utf8mb4'))),
);
```

Now the ORM will communicate with the MySQL database.

In this configuration the connection will stay open as long a you keep a reference to the manager in memory. However if you  want to use it in a long living process you should change a little how the adapter is constructed.

```php
Adapter\SQL::lazy(
    static fn() => $os->remote()->sql(Url::of('mysql://user:password@host:3306/database?charset=utf8mb4')),
);
```

In this configuration the adapter will close the connection when there is no more repository loaded in memory, but will re-open it when you start using a repository again. This prevents keeping a connection open while your app stays idle for a while.
