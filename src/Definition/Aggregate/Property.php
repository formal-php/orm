<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Formal\ORM\{
    Definition\Type,
    Raw,
};
use Innmind\Reflection\Extract;
use Innmind\Immutable\{
    Set,
    Maybe,
};

/**
 * @template T of object
 * @template K
 */
final class Property
{
    /** @var class-string<T> */
    private string $class;
    /** @var non-empty-string */
    private string $name;
    /** @var Type<K> */
    private Type $type;

    /**
     * @param class-string<T> $class
     * @param non-empty-string $name
     * @param Type<K> $type
     */
    private function __construct(string $class, string $name, Type $type)
    {
        $this->class = $class;
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @template A of object
     * @template B
     *
     * @param class-string<A> $class
     * @param non-empty-string $name
     * @param Type<B> $type
     *
     * @return self<A, B>
     */
    public static function of(string $class, string $name, Type $type): self
    {
        return new self($class, $name, $type);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Type<K>
     */
    public function type(): Type
    {
        return $this->type;
    }

    /**
     * The diff relies on the immutable nature of aggregates and the properties
     * being strictly typed
     *
     * This allows to not unwrap monadic types and accidently loading
     * unnecessary data
     *
     * @param T $then
     * @param T $now
     *
     * @return Maybe<Raw\Aggregate\Property>
     */
    public function diff(object $then, object $now): Maybe
    {
        $thenValue = (new Extract)($then, Set::of($this->name))
            ->flatMap(fn($properties) => $properties->get($this->name));
        $nowValue = (new Extract)($now, Set::of($this->name))
            ->flatMap(fn($properties) => $properties->get($this->name));

        /** @psalm-suppress MixedArgument No way to tell psalm the property type */
        return Maybe::all($thenValue, $nowValue)
            ->flatMap(
                static fn(mixed $then, mixed $now) => Maybe::just($now)
                    ->filter(static fn($now) => $now !== $then),
            )
            ->map(fn($value) => Raw\Aggregate\Property::of(
                $this->name,
                $this->type->normalize($value),
            ));
    }
}
