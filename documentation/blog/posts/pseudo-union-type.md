---
authors: [baptouuuu]
date: 2024-07-05
---

# Aggregate state and union types

Let's launch this new documentation by showing how the new `Just` specification opens an interesting Aggregate design.

<!-- more -->

A state can easily be described via an enum in an Aggregate property, and since `2.0.0` this is supported by default. But sometimes you need to _attach_ extra information. Before `2.2.0`, even if technically possible, this is kind of messy type wise.

Let's take the example of a `Blueprint` Aggregate for a house. This blueprint can be:

- in draft with the architect that last modified it
- pre-approved by an architect
- approved by both an architect and a client

And we need to be able to list aggregates for one of those states.

We could design it this way:

=== "Blueprint"
    ```php
    use Formal\ORM\Id;

    final readonly class Blueprint
    {
        /**
         * @param Id<self> $id
         * @param ?Id<Architect> $architect
         * @param ?Id<Client> $client
         */
        private function __construct(
            private Id $id,
            private State $state,
            private ?Id $architect,
            private ?Id $client,
        ) {}

        /**
         * @param Id<Architect> $archiect
         */
        public static function new(Id $archiect): self
        {
            return new self(
                Id::new(self::class),
                State::draft,
                $architect,
                null,
            );
        }

        /**
         * @param Id<Architect> $architect
         */
        public function preApprove(Id $architect): self
        {
            return new self(
                $this->id,
                State::preApproved,
                $architect,
                $this->client,
            );
        }

        /**
         * @param Id<Client> $client
         */
        public function approve(Id $client): self
        {
            return new self(
                $this->id,
                State::preApproved,
                $this->architect,
                $client,
            );
        }

        public function doStuff(): void
        {
            match ($this->state) {
                State::draft => null, // todo
                State::preApproved => null, // todo
                State::approved => null, // todo
            };
        }
    }
    ```

=== "State"
    ```php
    enum State
    {
        case draft;
        case preApproved;
        case approved;
    }
    ```

=== "Architect"
    ```php
    final readonly class Architect
    {
        /**
         * @param Id<self> $id
         */
        public function __construct(
            private Id $id,
        ) {}
    }
    ```

    This is a dummy aggregate only here for the example.

=== "Client"
    ```php
    final readonly class Client
    {
        /**
         * @param Id<self> $id
         */
        public function __construct(
            private Id $id,
        ) {}
    }
    ```

    This is a dummy aggregate only here for the example.

You can easily query aggregates by state via a simple [specification on the `state` property](../../specifications/index.md).

However type wise this is not great because in the `Blueprint::doStuff()` method [Psalm](https://psalm.dev) can't know that for each state the associated properties are not `null`. You need to either add extra null checks that are useless or add `@psalm-suppress` annotations that may hide real errors in the future.

With `2.2.0` we can redesign the aggregate this way:

=== "Blueprint"
    ```php
    use Formal\ORM\{
        Id,
        Definition\Contains,
    };
    use Innmind\Immutable\Maybe;

    final readonly class Blueprint
    {
        /**
         * @param Id<self> $id
         * @param Maybe<Draft> $draft
         * @param Maybe<PreApproved> $preApproved
         * @param Maybe<Approved> $approved
         */
        private function __construct(
            private Id $id,
            #[Contains(Draft::class)]
            private Maybe $draft,
            #[Contains(PreApproved::class)]
            private Maybe $preApproved,
            #[Contains(Approved::class)]
            private Maybe $approved,
        ) {}

        /**
         * @param Id<Architect> $archiect
         */
        public static function new(Id $archiect): self
        {
            return new self(
                Id::new(self::class),
                Maybe::just(new Draft($architect)),
                Maybe::nothing(),
                Maybe::nothing(),
            );
        }

        /**
         * @param Id<Architect> $architect
         */
        public function preApprove(Id $architect): self
        {
            return new self(
                $this->id,
                Maybe::nothing(),
                Maybe::just(new PreApproved($architect)),
                $this->approved,
            );
        }

        /**
         * @param Id<Client> $client
         */
        public function approve(Id $client): self
        {
            $architect = $this->preApproved->match(
                static fn($preApproved) => $preApproved->architect(),
                static fn() => throw new \LogicException('Not pre-approved'),
            );

            return new self(
                $this->id,
                Maybe::nothing(),
                Maybe::nothing(),
                Maybe::just(new Approved($architect, $client)),
            );
        }

        public function doStuff(): void
        {
            $state = $this
                ->draft
                ->otherwise(fn() => $this->preApproved)
                ->otherwise(fn() => $this->approved)
                ->match(
                    static fn($state) => $state,
                    static fn() => throw new \LogicException('Not reachable'),
                );

            match (true) {
                $state instanceof Draft => $state->architect(),
                $state instanceof PreApproved => $state->architect(),
                $state instanceof Approved => $state->client(),
            };
        }
    }
    ```

=== "Draft"
    ```php
    use Formal\ORM\Id;

    final readonly class Draft
    {
        /**
         * @param Id<Architect> $architect
         */
        public function __construct(
            private Id $architect,
        ) {}

        /**
         * @return Id<Architect>
         */
        public function architect(): Id
        {
            return $this->architect;
        }
    }
    ```

=== "PreApproved"
    ```php
    use Formal\ORM\Id;

    final readonly class PreApproved
    {
        /**
         * @param Id<Architect> $architect
         */
        public function __construct(
            private Id $architect,
        ) {}

        /**
         * @return Id<Architect>
         */
        public function architect(): Id
        {
            return $this->architect;
        }
    }
    ```

=== "Approved"
    ```php
    use Formal\ORM\Id;

    final readonly class Approved
    {
        /**
         * @param Id<Architect> $architect
         * @param Id<Client> $client
         */
        public function __construct(
            private Id $architect,
            private Id $client,
        ) {}

        /**
         * @return Id<Client>
         */
        public function client(): Id
        {
            return $this->client;
        }
    }
    ```

!!! success ""
    This new design has 2 benefits:

    - in the `approve` method we are forced to explicit the previous state to access the architect
    - in the `doStuff` method Psalm is now aware that the ids exist in each state

And it can be queried via this specification:

```php
use Formal\ORM\Specification\Just;
use Innmind\Specification\{
    Comparator,
    Composable,
    Sign,
};

/**
 * @psalm-immutable
 */
final readonly class State implements Comparator
{
    use Composable;

    public static function draft(): Just
    {
        return Just::of('draft', new self);
    }

    public static function preApproved(): Just
    {
        return Just::of('preApproved', new self);
    }

    public static function approved(): Just
    {
        return Just::of('approved', new self);
    }

    public function property(): string
    {
        return 'architect';
    }

    public function sign(): Sign
    {
        return Sign::isNotNull;
    }

    public function value(): mixed
    {
        return null;
    }
}
```

By checking the `architect` is not null allows to check if the entity exist in the storage.
