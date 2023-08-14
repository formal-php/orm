<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Formal\ORM\{
    Id,
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
final class Identity
{
    /** @var non-empty-string */
    private string $property;
    /** @var class-string<T> */
    private string $class;

    /**
     * @psalm-mutation-free
     *
     * @param non-empty-string $property
     * @param class-string<T> $class
     */
    private function __construct(string $property, string $class)
    {
        $this->property = $property;
        $this->class = $class;
    }

    /**
     * @internal
     * @psalm-pure
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
     * @psalm-mutation-free
     *
     * @return non-empty-string
     */
    public function property(): string
    {
        return $this->property;
    }

    /**
     * @param T $aggregate
     *
     * @return Id<T>
     */
    public function extract(object $aggregate): Id
    {
        /** @var Id<T> */
        return (new Extract)($aggregate, Set::of($this->property))
            ->flatMap(fn($properties) => $properties->get($this->property))
            ->keep(Instance::of(Id::class))
            ->match(
                static fn($id) => $id,
                fn() => throw new \LogicException("Unable to extract id on {$this->class}"),
            );
    }

    /**
     * @psalm-mutation-free
     *
     * @param Id<T> $id
     */
    public function normalize(Id $id): Raw\Aggregate\Id
    {
        return Raw\Aggregate\Id::of($this->property, $id->toString());
    }

    /**
     * @psalm-mutation-free
     *
     * @return Id<T>
     */
    public function denormalize(Raw\Aggregate\Id $id): Id
    {
        return Id::of($this->class, $id->value());
    }
}
