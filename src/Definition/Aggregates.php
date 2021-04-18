<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Innmind\Immutable\Map;

final class Aggregates
{
    /** @var Map<class-string, Aggregate<object>> */
    private Map $aggregates;

    private function __construct(Aggregate ...$aggregates)
    {
        /** @var Map<class-string, Aggregate<object>> */
        $this->aggregates = Map::of('string', Aggregate::class);

        foreach ($aggregates as $aggregate) {
            $this->aggregates = ($this->aggregates)($aggregate->class(), $aggregate);
        }
    }

    public static function of(Aggregate ...$aggregates): self
    {
        return new self(...$aggregates);
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return Aggregate<T>
     */
    public function get(string $class): Aggregate
    {
        if ($this->aggregates->contains($class)) {
            /** @var Aggregate<T> */
            return $this->aggregates->get($class);
        }

        return Aggregate::of($class);
    }
}
