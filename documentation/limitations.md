# Limitations

Due to the current design of entities not having ids it is not possible to build a diff of collections of entities. This means that as soon as a collection is modified the whole collection is persisted to the storage.

For small sets of entities this is fine but can become quite time consuming if you store a lot of data inside a given collection.
