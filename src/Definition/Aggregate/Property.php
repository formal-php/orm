<?php
declare(strict_types = 1);

namespace Formal\ORM\Definition\Aggregate;

use Formal\ORM\Definition\Type;

/**
 * @psalm-immutable
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
     * @psalm-pure
     *
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
}
