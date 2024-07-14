# SQL

You should use this storage for production mainly.

## Setup

=== "MySQL/MariaDB"
    ```php
    use Formal\ORM\Manager;
    use Innmind\OperatingSystem\Factory;
    use Innmind\Url\Url;

    $os = Factory::build(); //(1)
    $orm = Manager::sql(
        $os
            ->remote()
            ->sql(Url::of('mysql://user:password@127.0.0.1:3306/database_name')),
    );
    ```

    1. See [`innmind/operating-system`](https://innmind.github.io/documentation/getting-started/operating-system/).

=== "PostgreSQL"
    ```php
    use Formal\ORM\Manager;
    use Innmind\OperatingSystem\Factory;
    use Innmind\Url\Url;

    $os = Factory::build(); //(1)
    $orm = Manager::sql(
        $os
            ->remote()
            ->sql(Url::of('pgsql://user:password@127.0.0.1:5432/database_name')),
    );
    ```

    1. See [`innmind/operating-system`](https://innmind.github.io/documentation/getting-started/operating-system/).

## Mapping

By default when you'll generate the SQL to create the Aggregate tables ([see below](#creating-tables)) Formal will adapt the column types for the PHP types it handles by default. For any other type it will use `LONGTEXT` with a comment suggesting you to modify this type.

To avoid that you can declare the SQL type for your [custom types](../mapping/type.md). For the `Name` you could do:

```php title="NameType.php" hl_lines="3 5 11 13-16"
use Formal\ORM\{
    Definition\Type,
    Adapter\SQL\SQLType,
};
use Formal\AccessLayer\Table\Column;

/**
 * @psalm-immutable
 * @implements Type<Name>
 */
final class NameType implements Type, SQLType
{
    public function sqlType(): Column\Type
    {
        return Column\type::varchar(100);
    }

    public function normalize(mixed $value): null|string|int|bool
    {
        return $value->toString();
    }

    public function denormalize(null|string|int|bool $value): mixed
    {
        if (!\is_string($value)) {
            throw new \LogicException("'$value' is not a string");
        }

        return Name::of($value);
    }
}
```

## Creating tables

To generate the SQL queries to create the tables you can build a simple script like this:

```php title="show_create_tables.php"
use Formal\ORM\{
    Definition\Aggregates,
    Definition\Types,
    Definition\Type\Support,
    Adapter\SQL\ShowCreateTable,
};
use Formal\AccessLayer\Driver;
use Innmind\OperatingSystem\Factory;

$os = Factory::build(); //(1)
$aggregates = Aggregates::of(Types::of(
    Support::class(Name::class, new NameType),
));
$show = ShowCreateTable::of($aggregates);

$_ = $show(User::class)->foreach(
    static fn($query) => print($query->sql(Driver::mysql).";\n"),
);
```

1. See [`innmind/operating-system`](https://innmind.github.io/documentation/getting-started/operating-system/).

And if you run `#!sh php show_create_tables.php` it would output:

```sql
CREATE TABLE  `user` (`id` varchar(36) NOT NULL  COMMENT 'UUID', `name` varchar(100) NOT NULL  , PRIMARY KEY (`id`));
CREATE TABLE  `user_addresses` (`aggregateId` varchar(36) NOT NULL  COMMENT 'UUID', `street` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', `zipCode` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', `city` longtext NOT NULL  COMMENT 'TODO adjust the type depending on your use case', CONSTRAINT `FK_user_addresses` FOREIGN KEY (`aggregateId`) REFERENCES `user`(`id`) ON DELETE CASCADE);
```

Instead of printing the queries you can execute them directly like this:

```php title="show_create_tables.php" hl_lines="8 15-17 19"
use Formal\ORM\{
    Definition\Aggregates,
    Definition\Types,
    Definition\Type\Support,
    Adapter\SQL\ShowCreateTable,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;

$os = Factory::build(); //(1)
$aggregates = Aggregates::of(Types::of(
    Support::class(Name::class, new NameType),
));
$show = ShowCreateTable::of($aggregates);
$connection = $os->remote()->sql(
    Url::of('mysql://user:password@host:3306/database?charset=utf8mb4'),
);

$_ = $show(User::class)->foreach($connection);
```

## Migrations

While you still develop your app you can destroy and recreate your database when the schema change. But when you go to production you should use a migration tool to only update what's changed since the last deployment.

Unfortunately Formal doesn't have such tool _yet_. For now you can use [`doctrine/migrations`](https://packagist.org/packages/doctrine/migrations).
