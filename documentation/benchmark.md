---
hide:
    - navigation
    - toc
---

# Benchmark

A small benchmark as a reference point for the performance of this ORM consists in generating and persisting 100K users in a single transaction and then loading them.

```sh
time php benchmark/fill_storage.php
php benchmark/fill_storage.php  94.46s user 7.76s system 45% cpu 3:44.12 total
time php benchmark/load.php
Memory: 56.00 Mo
php benchmark/load.php  12.21s user 0.09s system 97% cpu 12.582 total
```

This means the ORM can load 1 aggregate in 0.1 millisecond.

This was run on a MacbookPro 16" with a M4 Pro with the mariadb running inside Docker.

!!! note ""
    If all the aggregates were to be stored in memory it would take around 2Go of RAM and 15 seconds to complete.
