<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Formal\ORM\Definition\Type\StringType;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @template T of object
 */
final class Collection
{
    /** @var class-string<T> */
    private string $class;
    /** @var non-empty-string */
    private string $name;
    /** @var Sequence<Property<T, mixed>> */
    private Sequence $properties;
    private bool $enum;

    /**
     * @param class-string<T> $class
     * @param non-empty-string $name
     * @param Sequence<Property<T, mixed>> $properties
     */
    private function __construct(
        string $class,
        string $name,
        Sequence $properties,
        bool $enum,
    ) {
        $this->class = $class;
        $this->name = $name;
        $this->properties = $properties;
        $this->enum = $enum;
    }

    /**
     * @internal
     * @psalm-pure
     * @template A of object
     *
     * @param class-string<A> $class
     * @param non-empty-string $name
     * @param Sequence<Property<A, mixed>> $properties
     *
     * @return self<A>
     */
    public static function of(
        string $class,
        string $name,
        Sequence $properties,
    ): self {
        return new self($class, $name, $properties, false);
    }

    /**
     * @internal
     * @psalm-pure
     * @template A of object
     *
     * @param class-string<A> $class
     * @param non-empty-string $name
     *
     * @return self<A>
     */
    public static function ofEnum(
        string $class,
        string $name,
    ): self {
        /** @psalm-suppress InvalidArgument */
        return new self(
            $class,
            $name,
            Sequence::of(Property::of(
                $class,
                'name',
                StringType::new(),
            )),
            true,
        );
    }

    /**
     * @return class-string<T>
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Sequence<Property<T, mixed>>
     */
    public function properties(): Sequence
    {
        return $this->properties;
    }

    /**
     * @psalm-assert-if-true class-string<\UnitEnum> $this->class
     * @psalm-assert-if-true class-string<\UnitEnum> $this->class()
     */
    public function enum(): bool
    {
        return $this->enum;
    }
}
