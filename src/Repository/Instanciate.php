<?php
declare(strict_types = 1);

namespace Formal\ORM\Repository;

use Formal\ORM\Definition\Aggregate as Definition;
use Innmind\Reflection;

/**
 * @template T of object
 */
final class Instanciate
{
    private Reflection\Instanciate $new;
    /** @var Definition<T> */
    private Definition $definition;
    /** @var class-string<T> */
    private string $class;

    /**
     * @param Definition<T> $definition
     */
    private function __construct(Definition $definition)
    {
        $this->new = new Reflection\Instanciate;
        $this->definition = $definition;
        $this->class = $definition->class();
    }

    /**
     * @param Denormalized<T> $denormalized
     *
     * @return T
     */
    public function __invoke(Denormalized $denormalized): object
    {
        $properties = $denormalized
            ->properties()
            ->put(
                $this->definition->id()->property(),
                $denormalized->id(),
            );

        /** @var T */
        return ($this->new)($this->class, $properties)->match(
            static fn($aggregate) => $aggregate,
            fn() => throw new \RuntimeException("Unable to denormalize aggregate of type '{$this->class}'"),
        );
    }

    /**
     * @template A of object
     *
     * @param Definition<A> $definition
     *
     * @return self<A>
     */
    public static function of(Definition $definition): self
    {
        return new self($definition);
    }
}
