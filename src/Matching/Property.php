<?php
declare(strict_types = 1);

namespace Formal\ORM\Matching;

use Formal\ORM\{
    Definition\Aggregate\Identity,
    Sort as SortedBy,
    Repository,
    Repository\Denormalize,
    Repository\Sort,
    Adapter,
    Adapter\Repository\CrossAggregateMatching,
    Adapter\Repository\SubMatch,
    Specification\Normalize,
    Id,
};
use Innmind\Specification\Specification;
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Predicate\Instance,
};

/**
 * @template T of object
 */
final class Property
{
    /**
     * @param Adapter\Repository<T> $adapter
     * @param Identity<T> $identity
     * @param Denormalize<T> $denormalize
     * @param ?Normalize<T> $normalizeSpecification
     * @param Sort<T> $sort
     * @param ?int<1, max> $drop
     * @param ?int<0, max> $take
     * @param non-empty-string $property
     */
    private function __construct(
        private Adapter\Repository $adapter,
        private Identity $identity,
        private Repository\Context $context,
        private Denormalize $denormalize,
        private ?Normalize $normalizeSpecification,
        private Sort $sort,
        private ?Specification $specification,
        private null|SortedBy\Property|SortedBy\Entity $sorted,
        private ?int $drop,
        private ?int $take,
        private string $property,
    ) {
    }

    /**
     * @internal
     * @template A of object
     *
     * @param Adapter\Repository<A> $adapter
     * @param Identity<A> $identity
     * @param Denormalize<A> $denormalize
     * @param Normalize<A> $normalizeSpecification
     * @param Sort<A> $sort
     * @param ?int<1, max> $drop
     * @param ?int<0, max> $take
     * @param non-empty-string $property
     *
     * @return self<A>
     */
    public static function of(
        Adapter\Repository $adapter,
        Identity $identity,
        Repository\Context $context,
        Denormalize $denormalize,
        ?Normalize $normalizeSpecification,
        Sort $sort,
        ?Specification $specification,
        null|SortedBy\Property|SortedBy\Entity $sorted,
        ?int $drop,
        ?int $take,
        string $property,
    ): self {
        return new self(
            $adapter,
            $identity,
            $context,
            $denormalize,
            $normalizeSpecification,
            $sort,
            $specification,
            $sorted,
            $drop,
            $take,
            $property,
        );
    }

    /**
     * @internal
     * @psalm-mutation-free
     *
     * @return SubMatch|Sequence<Id>
     */
    public function crossAggregateMatching(Repository\Context $context): SubMatch|Sequence
    {
        $take = $this->take;

        if ($take === 0) {
            return Sequence::of();
        }

        $specification = null;

        if ($this->normalizeSpecification && $this->specification) {
            $specification = ($this->normalizeSpecification)($this->specification);
        }

        /**
         * @var SubMatch|Sequence<Id>
         */
        return Maybe::just($this->context)
            ->filter($context->same(...))
            ->map(fn() => $this->adapter)
            ->keep(Instance::of(CrossAggregateMatching\Property::class))
            ->flatMap(fn($adapter) => $adapter->crossAggregateMatchingOnProperty(
                $this->property,
                $specification,
                $this->sorted,
                $this->drop,
                $take,
            ))
            ->match(
                static fn($subQuery) => $subQuery,
                fn() => $this->sequence(),
            );
    }

    /**
     * @return Sequence<Id>
     */
    private function sequence(): Sequence
    {
        if ($this->take === 0) {
            return Sequence::of();
        }

        $denormalize = ($this->denormalize)();
        $specification = null;

        if ($this->normalizeSpecification && $this->specification) {
            $specification = ($this->normalizeSpecification)($this->specification);
        }

        return $this
            ->adapter
            ->fetch(
                $specification,
                $this->sorted,
                $this->drop,
                $this->take,
            )
            ->map($denormalize)
            ->flatMap(
                fn($aggregate) => match ($this->property) {
                    $this->identity->property() => Sequence::of($aggregate->id()),
                    default => $aggregate
                        ->properties()
                        ->get($this->property)
                        ->keep(Instance::of(Id::class))
                        ->toSequence(),
                },
            );
    }
}
