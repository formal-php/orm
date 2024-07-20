# Mapping

So far the `User` aggregate only contains a `string` property. By default Formal also supports these primitive types:

- `bool`
- `int`
- `string`
- `?bool`
- `?int`
- `?string`

Using primitive types is fine when you prototype the design of your aggregates. But you **SHOULD** use dedicated classes for each kind of value to better convey meaning and expected behaviour, this is what [_Typing_](https://innmind.github.io/documentation/philosophy/explicit/#parse-dont-validate) is truely about. (1)
{.annotate}

1. It also has the benefit to immensely simplify refactoring your code.

Types is essential in the Formal design. You'll learn in the next chapter how to support your custom types.

By default Formal also supports:

<div class="annotate" markdown>
- `Innmind\Immutable\Str` from [`innmind/immutable`](https://packagist.org/packages/innmind/immutable) (1)
- `Innmind\TimeContinuum\PointInTime` from [`innmind/time-continuum`](https://packagist.org/packages/innmind/time-continuum)
</div>

1. Beware! It won't store the encoding, when fetched it will use `#!php Innmind\Immutable\Str\Encoding::utf8`

??? note
    Formal can support the `PointInTime` type but you still need to declare it like this:

    ```php
    use Formal\ORM\{
        Manager,
        Definition\Aggregates,
        Definition\Types,
        Definition\Type\PointInTime,
    }

    $orm = Manager::of(
        /* any adapter (1) */,
        Aggregates::of(
            Types::of(
                PointInTimeType::of($os->clock()),
            ),
        ),
    );
    ```

    1. See the [Adapters](../adapters/index.md) chapter to see all the adapters you can use.

    The `$os` variable comes from the [`innmind/operating-system`](https://innmind.github.io/documentation/getting-started/operating-system/) package.
