---
hide:
    - navigation
    - toc
---

# Pagination

Formal is optimized to be memory efficient so you can do this without running out of memory:

```php
$orm
    ->repository(User::class)
    ->all()
    ->foreach(static fn(User $user) => businessLogic($user));
```

But if you're building a HTTP API you don't want to return all the aggregates from your storage in the response. The go-to approach is to use pagination and return a fixed amount of aggregates.

This is very simple with Formal:

=== "Page 0"
    ```php
    use Formal\ORM\Sort;

    $usersArray = $orm
        ->repository(User::class)
        ->all()
        ->sort('name', Sort::asc)
        ->take(100)
        ->sequence()
        ->toList();
    ```

=== "Page 1"
    ```php
    use Formal\ORM\Sort;

    $usersArray = $orm
        ->repository(User::class)
        ->all()
        ->sort('name', Sort::asc)
        ->drop(100)
        ->take(100)
        ->sequence()
        ->toList();
    ```

=== "Page 2"
    ```php
    use Formal\ORM\Sort;

    $usersArray = $orm
        ->repository(User::class)
        ->all()
        ->sort('name', Sort::asc)
        ->drop(200)
        ->take(100)
        ->sequence()
        ->toList();
    ```

=== "Page 3"
    ```php
    use Formal\ORM\Sort;

    $usersArray = $orm
        ->repository(User::class)
        ->all()
        ->sort('name', Sort::asc)
        ->drop(300)
        ->take(100)
        ->sequence()
        ->toList();
    ```

=== "etc..."
    ```php
    use Formal\ORM\Sort;

    $page = 4;
    $pageSize = 100;
    $usersArray = $orm
        ->repository(User::class)
        ->all()
        ->sort('name', Sort::asc)
        ->drop($page * $pageSize)
        ->take($pageSize)
        ->sequence()
        ->toList();
    ```

!!! tip ""
    This also works with `->repository()->matching()`.

The sort allows the pagination to be _stable_ (the same query will return the same results).

??? warning
    The order of `drop` and `take` is important.

    The repository is treated as a _virtual `Sequence`_ for design consistency. If you take `100` aggregates and then drop `1_000` then the result is necessarily empty.

    This allows these 2 examples to be equivalent:

    === "Storage optimized"
        ```php
        use Formal\ORM\Sort;

        $page = 1;
        $pageSize = 100;
        $usersArray = $orm
            ->repository(User::class)
            ->all()
            ->sort('name', Sort::asc)
            ->drop($page * $pageSize)
            ->take($pageSize)
            ->sequence()
            ->toList();
        ```

    === "In memory"
        ```php
        use Formal\ORM\Sort;

        $page = 1;
        $pageSize = 100;
        $usersArray = $orm
            ->repository(User::class)
            ->all()
            ->sort('name', Sort::asc)
            ->sequence()
            ->drop($page * $pageSize)
            ->take($pageSize)
            ->toList();
        ```
