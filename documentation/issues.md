---
hide:
    - navigation
---

# Known issues

## Elasticsearch

### Searching with `endsWith`

When searching aggregates via a [specification](specifications/index.md) with `Sign::endsWith` you may not always see all the results.

Internally this search uses the [`wildcard` query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html) with the value starting with `*`. As described in the documentation this **SHOULD NOT** be used as it's an expensive query.

If you really need to do this kind of search you could add an extra property on your aggregate with the string being in reversed order from the original one. You can then do a search on this property with `Sign::startsWith` and reversing the string used as argument.

!!! warning ""
    Bear in mind that `startsWith` also uses the `wildcard` query and may slower that you'd want or even not return the results you'd expect.
