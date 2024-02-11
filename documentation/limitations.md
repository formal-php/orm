# Limitations

Due to the current design of entities not having ids it is not possible to build a complete diff of collections of entities. This means that as soon as an entity is modified the whole object is persisted to the storage.

For small entities this is fine but can become quite time consuming if you store a lot of data inside a given entity.
