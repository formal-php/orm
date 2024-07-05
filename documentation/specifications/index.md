# Specifications

Specifications is a [pattern](https://en.wikipedia.org/wiki/Specification_pattern) to describe a tree of conditions. It's composed of:

- a way to describe a comparison
- an `AND` _gate_
- an `OR` _gate_
- a `NOT` _gate_

This is the basis of boolean logic.

The big advantage is that a specification can be translated to many languages: pure PHP, SQL, Elasticsearch query, and more...

That's why Formal uses them to _target_ multiple aggregates, it allows to provide multiple storages and still be optimized for all of them.

Another big advantage is that they compose very easily. This allows to both abstract complex queries behind a domain semantic and tweak a query locally for a specific use case without having to duplicate the whole query.
