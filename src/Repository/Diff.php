<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\{
    Definition\Aggregate as Definition,
    Raw,
};
use Innmind\Immutable\{
    Map,
    Set,
};

/**
 * The diff relies on the immutable nature of aggregates and the properties
 * being strictly typed
 *
 * This allows to not unwrap monadic types and accidently loading unncessary
 * data
 *
 * @template T of object
 */
final class Diff
{
    /** @var Normalize<T> */
    private Normalize $normalize;

    /**
     * @param Normalize<T> $normalize
     */
    private function __construct(Normalize $normalize)
    {
        $this->normalize = $normalize;
    }

    /**
     * @param T $then
     * @param T $now
     */
    public function __invoke(object $then, object $now): Raw\Diff
    {
        $then = ($this->normalize)($then);
        $now = ($this->normalize)($now);
        $nowEntities = Map::of(
            ...$now
                ->entities()
                ->map(static fn($entity) => [$entity->name(), $entity])
                ->toList(),
        );

        $properties = self::diffProperties($then->properties(), $now->properties());
        $entities = $then->entities()->flatMap(
            static fn($then) => $nowEntities
                ->get($then->name())
                ->map(static fn($now) => self::diffEntities($then, $now))
                ->filter(static fn($now) => !$now->properties()->empty())
                ->toSequence()
                ->toSet(),
        );

        return Raw\Diff::of(
            $then->id(),
            $properties,
            $entities,
        );
    }

    /**
     * @template A of object
     *
     * @param Normalize<A> $normalize
     *
     * @return self<A>
     */
    public static function of(Normalize $normalize): self
    {
        return new self($normalize);
    }

    private static function diffEntities(
        Raw\Aggregate\Entity $then,
        Raw\Aggregate\Entity $now,
    ): Raw\Aggregate\Entity {
        return Raw\Aggregate\Entity::of(
            $then->name(),
            self::diffProperties($then->properties(), $now->properties()),
        );
    }

    /**
     * @param Set<Raw\Aggregate\Property> $then
     * @param Set<Raw\Aggregate\Property> $now
     *
     * @return Set<Raw\Aggregate\Property>
     */
    private static function diffProperties(Set $then, Set $now): Set
    {
        $nowProperties = Map::of(
            ...$now
                ->map(static fn($property) => [$property->name(), $property])
                ->toList(),
        );

        return $then->flatMap(
            static fn($then) => $nowProperties
                ->get($then->name())
                ->filter(static fn($now) => $then->value() !== $now->value())
                ->toSequence()
                ->toSet(),
        );
    }
}
