---
hide:
    - navigation
    - toc
---

# Benchmark

A small benchmark as a reference point for the performance of this ORM consists in generating and persisting 100K users in a single transaction and then loading them.

```sh
time php benchmark/fill_storage.php
php benchmark/fill_storage.php  222.24s user 5.20s system 60% cpu 6:18.40 total
time php benchmark/load.php
Memory: 40.00 Mo
php benchmark/load.php  11.06s user 0.08s system 97% cpu 11.388 total
```

This means the ORM can load 1 aggregate in 0.1 millisecond.

This was run on a MacbookPro 16" with a M1 Max with the mariadb running inside Docker.

!!! note ""
    If all the aggregates were to be stored in memory it would take around 2Go of RAM and 15 seconds to complete.
