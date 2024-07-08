<?php
declare(strict_types = 1);

namespace Properties\Formal\ORM;

use Innmind\BlackBox\{
    Set,
    Property,
};

final class Properties
{
    public static function any(array $properties = null): Set\Properties
    {
        return Set\Properties::any(
            ...\array_map(
                static fn($property) => [$property, 'any'](),
                $properties ?? self::list(),
            ),
        );
    }

    /**
     * @return non-empty-list<class-string<Property>>
     */
    public static function list(): array
    {
        return [
            AddAggregate::class,
            UpdateAggregate::class,
            UpdateEntity::class,
            UpdateOptional::class,
            UpdateCollection::class,
            UpdateCollectionOfEnums::class,
            SavingAggregateTwiceAddsItOnce::class,
            ContainsAggregate::class,
            RemoveUnknownAggregateDoesNothing::class,
            RemoveAggregate::class,
            RemoveSpecification::class,
            Size::class,
            SizeWithSpecification::class,
            Any::class,
            None::class,
            Matching::class,
            MatchingIds::class,
            MatchingEntity::class,
            MatchingCollection::class,
            MatchingOptional::class,
            MatchingSort::class,
            MatchingSortEntity::class,
            MatchingTake::class,
            MatchingDrop::class,
            MatchingDropAndTake::class,
            MatchingExclusion::class,
            MatchingComposite::class,
            SuccessfulTransaction::class,
            FailingTransactionDueToLeftSide::class,
            FailingTransactionDueToException::class,
            StreamUpdate::class,
            DroppingMoreElementsThanWasTakenReturnsNothing::class,
            AddingOutsideOfTransactionIsNotAllowed::class,
            UpdatingOutsideOfTransactionIsNotAllowed::class,
            RemovingOutsideOfTransactionIsNotAllowed::class,
            IncrementallyAddElementsToACollection::class,
            AddElementToCollections::class,
            ListingAggregatesUseConstantMemory::class,
        ];
    }

    /**
     * @return non-empty-list<class-string<Property>>
     */
    public static function withoutTransactions(): array
    {
        return \array_values(\array_filter(
            self::list(),
            static fn($class) => !\in_array(
                $class,
                [
                    FailingTransactionDueToLeftSide::class,
                    FailingTransactionDueToException::class,
                ],
                true,
            ),
        ));
    }

    /**
     * @return non-empty-list<class-string<Property>>
     */
    public static function alwaysApplicable(): array
    {
        return [
            AddAggregate::class,
            UpdateAggregate::class,
            UpdateEntity::class,
            UpdateOptional::class,
            UpdateCollection::class,
            UpdateCollectionOfEnums::class,
            SavingAggregateTwiceAddsItOnce::class,
            ContainsAggregate::class,
            RemoveUnknownAggregateDoesNothing::class,
            RemoveAggregate::class,
            RemoveSpecification::class,
            Size::class,
            SizeWithSpecification::class,
            Any::class,
            None::class,
            Matching::class,
            MatchingIds::class,
            MatchingEntity::class,
            MatchingCollection::class,
            MatchingOptional::class,
            MatchingSort::class,
            MatchingSortEntity::class,
            MatchingTake::class,
            MatchingDrop::class,
            MatchingDropAndTake::class,
            MatchingExclusion::class,
            MatchingComposite::class,
            SuccessfulTransaction::class,
            FailingTransactionDueToLeftSide::class,
            FailingTransactionDueToException::class,
            DroppingMoreElementsThanWasTakenReturnsNothing::class,
            AddingOutsideOfTransactionIsNotAllowed::class,
            IncrementallyAddElementsToACollection::class,
        ];
    }
}
