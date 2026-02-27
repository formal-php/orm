# Getting started

## Installation

```sh
composer require formal/orm
```

## Setup

=== "Ephemeral storage"
    ```php
    use Formal\ORM\Manager;
    use Innmind\Filesystem\Adapter;

    $orm = Manager::filesystem(Adapter::inMemory());
    ```

=== "Persistent storage"
    ```php
    use Formal\ORM\Manager;
    use Innmind\Filesystem\Adapter;
    use Innmind\Url\Path;

    $orm = Manager::filesystem(Adapter::mount(Path::of('some/directory/'))->unwrap());
    ```

!!! info ""
    In the rest of this documentation the variable `$orm` will reference this `Manager` object.

While you learn how to use this ORM the filesystem storage is enough, you'll learn later on how to use [other adapters](../adapters/index.md). As the examples names suggest one is ephemeral meaning nothing is persisted to the filesystem allowing you to run your code without side effects. On the other hand the _persistent_ storage will store the data to your filesystem and you'll need to delete the data when the aggregate class will change.
