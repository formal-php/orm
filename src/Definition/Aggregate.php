<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Formal\ORM\Id;
use Innmind\Reflection\ReflectionClass;
use Innmind\Immutable\{
    Str,
    Set,
    Monoid\Concat,
    Predicate\Instance,
};

/**
 * @template T of object
 */
final class Aggregate
{
    /** @var class-string<T> */
    private string $class;

    /**
     * @param class-string<T> $class
     */
    private function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * @template A
     *
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function of(string $class): self
    {
        return new self($class);
    }

    public function name(): string
    {
        return Str::of($this->class)
            ->split('\\')
            ->takeEnd(1)
            ->fold(new Concat)
            ->toLower()
            ->toString();
    }

    public function id(): Aggregate\Id
    {
        return ReflectionClass::of($this->class)
            ->properties()
            ->filter(static fn($property) => $property->type()->toString() === Id::class)
            ->filter(
                fn($property) => $property
                    ->attributes()
                    ->filter(static fn($attribute) => $attribute->class() === Template::class)
                    ->map(static fn($attribute) => $attribute->instance())
                    ->keep(Instance::of(Template::class))
                    ->any(fn($template) => $template->is($this->class)),
            )
            ->find(static fn() => true) // TODO mention in the doc that only one property can reference an id of the current aggregate
            ->match(
                fn($property) => Aggregate\Id::of($property->name(), $this->class),
                static fn() => throw new \LogicException('One proper must be typed Id<self>'),
            );
    }

    /**
     * @return Set<Aggregate\Property>
     */
    public function properties(): Set
    {
        return Set::of();
    }
}
