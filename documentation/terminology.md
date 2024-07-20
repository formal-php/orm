# Terminology

This ORM uses a specific terminlogy to reference behaviours and data structures. If your familiar with [Domain-Driven Design](https://en.wikipedia.org/wiki/Domain-driven_design), you should feel at home.

## Manager

The `Manager` is the entrypoint to everything you can achieve with this ORM. It allows to:

- access [repositories](#repository)
- create [transactions](#transaction)

## Adapter

An `Adapter` is the kind of storage you want to use. This is an object you need to create to pass as an argument to a `Manager`.

This ORM comes buit-in with [many adapters](adapters/index.md).

## Repository

A `Repository` is kind of a big collection that represents all the [aggregates](#aggregate) of a given type. This is via this abstraction that you will persist and [query](#specification) your data.

## Transaction

This is the only place where you can modify the data inside a [repository](#repository).

A transaction is expressed via a `callable` passed to the [manager](#manager), if the `callable` finishes successfully then all the modifications are committed to the storage otherwise they're rollbacked.

The `callable` must return an [`Either`](https://innmind.github.io/documentation/getting-started/handling-data/either/) monad where its right side is the only case where the transaction is considered successful. A left side or an exception is considered a failure.

## Aggregate

It represents a root object to encapsulate data and behaviour. When accessing a [repository](#repository) this is this class you need to specify.

An aggregate can contain properties like any object, required entities, optional entities and collections of entities.

In order to be persisted all properties of aggregates and entities must be typed.

## Entity

An `Entity` is a sub object solely owned by an `Aggregate` that needs to store properties. An entity can be:

- required: this means it always exist as long the aggregate exists and is a normal property of an aggregate
- optional: this means the entity is wrapped inside a `Maybe` monad
- in a collection: this means multiple entities of the same type are wrapped in a `Set` monad

An entity can only contain properties, it can't contain other entities.

!!! tip ""
    You can use Enums as entities without the need to wrap them inside another class.

## Specification

A `Specification` is the mechanism to create a filter. It's used to retrieve aggregates or remove them.

It uses objects to represent:

- a comparison
- a negation
- an `and` composition of 2 specifications
- an `or` composition of 2 specifications

In essence this boolean logic represented via objects. With them you can create almost any filter. And it enforces the precedence of operations (thus no implicits).
