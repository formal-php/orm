<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect\Collection;

use Innmind\Immutable\Sequence;

/**
 * @internal
 * @psalm-immutable
 */
final class Add
{
    /**
     * @param non-empty-string $property
     * @param Sequence<object> $entities
     */
    private function __construct(
        private string $property,
        private Sequence $entities,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(string $property, object $entity): self
    {
        return new self($property, Sequence::of($entity));
    }

    /**
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    /**
     * @return Sequence<object>
     */
    public function entities(): Sequence
    {
        // It's not currently possible to specify multiple entities to add at
        // once because of the SQL adapter. To insert multiple entities it
        // requires to run multiple "INSERT" queries. And if a specification is
        // passed to condition to which aggregate add the children then it uses
        // the "INSERT INTO SELECT" strategy. The problem is that the condition
        // on the "SELECT" may depend on the collections being modified. This
        // means that between 2 "INSERT"s it may not affect the same aggregates.
        // This is an implicit behaviour that may lead to bugs.
        // A possible solution would be to use a CTE to make sure the list of
        // aggregates for all "INSERT"s. But this needs quite some work to
        // achieve.
        // For now this method returns a Sequence to allow to add this feature
        // in the future without introducing a BC break.
        return $this->entities;
    }
}
