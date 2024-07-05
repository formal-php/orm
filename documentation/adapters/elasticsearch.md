# Elasticsearch

You should use this storage when you want a super fast search.

## Setup

```php
use Formal\ORM\{
    Manager,
    Adapter\Elasticsearch,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;

$os = Factory::build(); //(1)
$orm = Manager::of(
    Elasticsearch::of(
        $os->remote()->http(),
        Url::of('http://localhost:9200/'), //(2)
    ),
);
```

1. See [`innmind/operating-system`](https://innmind.github.io/documentation/getting-started/operating-system/).
2. If you use this exact url then you can omit this parameter.

## Mapping

By default when you'll generate the mapping to create the Aggregate index ([see below](#creating-indexes)) Formal will adapt the field types for the PHP types it handles by default. For any other type it will use `text`.

To avoid that you can declare the mapping for your [custom types](../mapping/type.md). For the `Name` you could do:

```php title="NameType.php" hl_lines="3 5 11 13-19"
use Formal\ORM\{
    Definition\Type,
    Adapter\Elasticsearch\ElasticsearchType,
};
use Formal\AccessLayer\Table\Column;

/**
 * @psalm-immutable
 * @implements Type<Name>
 */
final class NameType implements Type, ElasticsearchType
{
    public function elasticsearchType(): array
    {
        return [
            'type' => 'keyword', //(1)
            'index' => false,
        ];
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

1. See the [documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-types.html) for an exhaustive list of types you can use.

## Creating indexes

To automatically create the index you can build a simple script like this:

```php title="show_create_tables.php"
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
$url = Url::of('http://localhost:9200/');
$createIndex = CreateIndex::of(
    $os->remote()->http(),
    $aggregates,
    $url,
);
$dropIndex = DropIndex::of(
    $os->remote()->http(),
    $aggregates,
    $url,
);

$_ = $dropIndex(User::class)
    ->flatMap(static fn() => $createIndex(User::class))
    ->match(
        static fn() => null, // index available
        static fn() => throw new \RuntimeException('Unable to create User index'),
    );
```

1. See [`innmind/operating-system`](https://innmind.github.io/documentation/getting-started/operating-system/).

## Limitations

!!! warning ""
    While this storage as its usages don't forget about [its limitations](../limitations.md#elasticsearch).
