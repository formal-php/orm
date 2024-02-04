# Philosophy

This ORM goal is to allow users to store data in long living processes (like a daemon or a queue consumer) and asynchronous apps.

The long living processes use case implies that the ORM must be memory efficient to avoid memory leaks that would crash your app. The asynchronous use case implies that the ORM must use an generic abstraction allowing it to not be aware if it's used in a synchronous or asynchronous context, this means that no code needs to be changed for this to work.

This goal is achieved by:
- using Monads
- using Trees instead of a Graph
- using immutable Aggregates

## Monads

In order to be memory efficient we need to represent a collection of data that can be streamed. This is why this ORM uses the `Sequence` monad when fetching multiple aggregates.

For design consistency this ORM uses the `Maybe` monad when fetching an aggregate by id, instead of returning the aggregate or throwing an exception when no value is found. This also allows the retrieval to be deferred, meaning that if you never unwrap the monad there will be no call made to the storage.

The `Maybe` monad is also used to wrap optional entities in your aggregates meaning that these entities are not fetched unless you need it to (and the eventual fetch from the storage is transparent in your code). But once loaded it stays in memory, as long as your aggregate is in memory.

Collections of entities in an aggregate is achieved using the `Set` monad and works the same way as `Maybe`, no data fetched by default but once it is loaded it stays in memory.

> [!NOTE]
> the monads mentionned above come from [`innmind/immutable`](https://packagist.org/packages/innmind/immutable).

## Trees, not a Graph

Traditionnally ORMs use the same data representation as the SQL database they try to abstract. And since SQL is about relations you end up with your objects pointing to each other resulting in a big Graph of objects. To avoid loading your whole database in memory these ORMs use proxy objects (and thus use inheritance) to load relationships only when used.

The problem with this approach is that you may still reach a memory exhaustion because once an object is loaded it stays in memory even if you no longer need it.

This ORM partly move away from the SQL model by using Trees. A tree is a tree of objects meaning a root object is the only _owner_ of the objects it references. This allows to safely free memory when you no longer use this root object as no other object has ownership of the relations.

In this package a Tree is called an Aggregate and objects it references Entities. This terminology comes from the [Domain Driven Design](https://en.wikipedia.org/wiki/Domain-driven_design) concept.

This is because each Aggregate is independent and encapsulate ownership of data that we can stream them via the `Sequence` monad.

## Immutability

In order to make sure an Aggregate is the only owner of the data it's supposed to encapsulate an Aggregate **MUST** be immutable. This means that if you want to update your aggregate data you must create a copy of this aggregate with the data modified.

It is thanks to immutability that it is guaranteed that there is only one owner of any object AND that the ORM is able to compute a diff to only update the data that changed since when you fetched the aggregate.

Since the ORM doesn't need to create proxies for your objects you can declare all your classes `final` so no one can change their behaviour.

Immutability also reduces the risk that you start modifying an aggregate that is accidently persisted to your database when some code ask to flush all changes. Since aggregates are immutable you have to explicitly call your repository with the new aggregate version.
