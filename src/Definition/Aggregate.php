<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition;

use Formal\ORM;
use Innmind\Reflection\ReflectionClass;
use Innmind\Immutable\{
    Set,
    Exception\NoElementMatchingPredicateFound,
};

final class Aggregate
{
    /** @var class-string */
    private string $class;
    /** @var Set<string> */
    private Set $exclude;

    /**
     * @param class-string $class
     */
    private function __construct(string $class)
    {
        $this->class = $class;
        $this->exclude = Set::strings();
    }

    /**
     * @param class-string $class
     */
    public static function of(string $class): self
    {
        return new self($class);
    }

    public function exclude(string $property): self
    {
        $self = clone $this;
        $self->exclude = ($this->exclude)($property);

        return $self;
    }

    /**
     * @throws \LogicException When no id defined in the aggregate
     */
    public function id(): Id
    {
        try {
            return new Id(
                $this
                    ->properties()
                    ->find(static fn($property) => $property->type()->ofClass(ORM\Id::class))
                    ->name(),
            );
        } catch (NoElementMatchingPredicateFound $e) {
            throw new \LogicException("No id property defined for '{$this->class}'");
        }
    }

    /**
     * @return Set<Property>
     */
    public function properties(): Set
    {
        return ReflectionClass::of($this->class)
            ->properties()
            ->filter(fn($property) => !$this->exclude->contains($property))
            ->mapTo(
                Property::class,
                fn($property) => Property::of($this->class, $property),
            );
    }
}
