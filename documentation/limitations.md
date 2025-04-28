# Limitations

## Entity collection

Due to the current design of entities not having ids it is not possible to build a diff of collections of entities. This means that as soon as a collection is modified the whole collection is persisted to the storage.

For small sets of entities this is fine but can become quite time consuming if you store a lot of data inside a given collection.

## Elasticsearch

This adapter has 2 major limitations:

- it does not support transactions
- it can't list more than 10k aggregates

Elasticsearch have no concept of transactions. The adapter implementation do not try to emulate a transaction mechanism as it would be too complex. This means that has soon you make an operation on a repository the change is directly applied to the underlying index.

The Elasticsearch api doesn't allow a pagination above 10k documents. This is a hardcoded behaviour on their part, this is a design choice as to not interpret an index as a database. This means that if you have more than 10k aggregates you won't be able to list them all.

!!! warning ""
    These limitations mean that you can't swap another adapter by this one without behaviours changes in your app.

## Effects

It's not possible to apply multiple effects at once. You'll need to apply each one of them individually, and can still be done in a same transaction.

This is due to a current limitation of the SQL adapter.

To apply multiple effects would require to execute multiple queries. But the aggregates matched by the specification could change between each query in the case an effect change a value being matched by the specification.

To lift this limitation requires a significant change of the SQL adapter.
