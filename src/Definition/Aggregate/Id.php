<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Formal\ORM\{
    Id as PublicId,
    Raw,
};
use Innmind\Reflection\Extract;
use Innmind\Immutable\{
    Set,
    Predicate\Instance,
};

/**
 * @template T of object
 */
final class Id
{
    /** @var non-empty-string */
    private string $property;
    /** @var class-string<T> */
    private string $class;

    /**
     * @param non-empty-string $property
     * @param class-string<T> $class
     */
    private function __construct(string $property, string $class)
    {
        $this->property = $property;
        $this->class = $class;
    }

    /**
     * @template A
     *
     * @param non-empty-string $property
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function of(string $property, string $class): self
    {
        return new self($property, $class);
    }

    /**
     * @param T $aggregate
     *
     * @return PublicId<T>
     */
    public function extract(object $aggregate): PublicId
    {
        /** @var PublicId<T> */
        return (new Extract)($aggregate, Set::of($this->property))
            ->flatMap(fn($properties) => $properties->get($this->property))
            ->keep(Instance::of(PublicId::class))
            ->match(
                static fn($id) => $id,
                fn() => throw new \LogicException("Unable to extract id on {$this->class}"),
            );
    }

    /**
     * @param PublicId<T> $id
     */
    public function normalize(PublicId $id): Raw\Aggregate\Id
    {
        return Raw\Aggregate\Id::of();
    }
}
