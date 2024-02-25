# Terminology

## Manager

The `Manager` is the entrypoint to everything you can achieve with this ORM. It allows to:
- access [repositories](#repository)
- create [transactions](#transaction)

## Adapter

An `Adapter` is the kind of storage you want to use. This is an object you need to create to pass as an argument to a `Manager`.

This ORM comes buit-in with 2: Filesystem and SQL.

## Repository

A `Repository` is kind of a big collection that represents all the [aggregates](#aggregate) of a given type. This is via this abastraction that you will persist and [query](#specification) your data.

## Transaction

This is the only place where you can modify the data inside a [repository](#repository).

A transaction is expressed via a `callable` passed to the [manager](#manager), if the `callable` finishes successfully then all the modifications are committed to the storage otherwise they're rollbacked.

The `callable` must return an `Either` monad where its right side is the only case where the transaction is considered successful. A left side or an exception is considered a failure.

## Aggregate

This concept comes [Domain Driven Design](https://en.wikipedia.org/wiki/Domain-driven_design) and represent a root object to encapsulate data and behaviour. When accessing a [repository](#repository) this is this call you need to specify.

An aggregate can contain properties like any object, required entities, optional entities and collections of entities.

In order to be persisted all properties of aggregates and entities must be typed.

## Entity

An `Entity` is a sub object solely owned by an `Aggregate` that needs to store properties. An entity can be:
- required: this means it always exist as long the aggregate exists and is a normal property of an aggregate
- optional: this means the entity is wrapped inside a `Maybe` monad
- in a collection: this means multiple entities of the same type are wrapped in a `Set` monad

An entity can only contain properties, it can't contain other entities.

> [!TIP]
> You can use Enums as entities without the need to wrap them inside another class.

## Specification

A `Specification` is the only way to retrieve a filtered `Sequence` from a repository. Conceptually it is a tree of objects where each can be:
- a comparison
- a negation
- an `and` composition of 2 specifications
- an `or` composition of 2 specifications

This concept allows to express any condition and impose the expression of the precedence of operations (thus no implicits between `and`, `or` and `not`).
