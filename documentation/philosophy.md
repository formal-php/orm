# Philosophy

This ORM goal is to allow users to store data in long living processes (like a daemon or a queue consumer) and asynchronous apps.

The long living processes use case implies that the ORM must be memory efficient to avoid memory leaks that would crash your app. The asynchronous use case implies that the ORM must be agnostic of the context in which it's run, this means that no code needs to be changed for this to work.

This goal is achieved by:

- using Monads
- using Trees instead of a Graph
- using immutable Aggregates

!!! abstract ""
    By being strict in its design this ORM also prevents you from using it in a way that it doesn't support. It's intended to push you to find an alternative tool instead of letting you shoot yourself in the foot.

## Monads

To be memory efficient we need to represent a collection of data that can be streamed. This is why this ORM uses the [`Sequence`](https://innmind.github.io/documentation/getting-started/handling-data/sequence/) monad when fetching aggregates.

For design consistency this ORM uses the [`Maybe`](https://innmind.github.io/documentation/getting-started/handling-data/maybe/) monad when fetching an aggregate by id. Instead of returning the aggregate or throwing an exception when no value is found. This also allows the retrieval to be deferred. Meaning that if you never unwrap the monad there will be no call made to the storage.

The `Maybe` monad is also used to wrap optional entities in your aggregates. Meaning that these entities are not fetched unless you need it to. The eventual fetch from the storage is transparent in your code. But once loaded it stays in memory, as long as your aggregate is in memory.

The [`Set`](https://innmind.github.io/Immutable/structures/set/) monad is used to represent collections of entities and works the same way as `Maybe`. No data fetched by default but once it is loaded it stays in memory.

## Trees, not a Graph

Traditionnally ORMs use the same data representation as the SQL database they try to abstract. And since SQL is about relations you end up with your objects pointing to each other resulting in a big Graph of objects. To avoid loading your whole database in memory these ORMs use proxy objects (and thus use inheritance) to load relationships only when used.

The problem with this approach is that you may still reach a memory exhaustion because once an object is loaded it stays in memory even if you no longer need it.

This ORM partly move away from the SQL model by using Trees. A tree is a tree of objects meaning a root object is the only _owner_ of the objects it references. This allows to safely free memory when you no longer use this root object as no other object has ownership of the relations.

In this package a Tree is called an [Aggregate](terminology.md#aggregate) and objects it references [Entities](terminology.md#entity). This terminology comes from the [Domain Driven Design](https://en.wikipedia.org/wiki/Domain-driven_design) concept.

This is because each Aggregate is independent and encapsulate ownership of data that we can stream them via the `Sequence` monad.

## Immutability

To make sure an Aggregate is the only owner of the data it's supposed to encapsulate it **MUST** be immutable. This means that if you want to update its data you must create a copy with the data modified.

Thanks to immutability it guarantees that there is only one owner of any object. The ORM is then able to compute a diff to only update the data that changed since when you fetched the aggregate.

The ORM doesn't need to create proxies for your objects. This means you can declare all your classes `final` so no one can change their behaviour.

Immutability also reduces the risk to persist partial modifications. Any modification of an aggregate returns a copy. This means you have to explicitly call the repository to apply a change.
