<?php
declare(strict_types = 1);

namespace Formal\ORM;

use Ramsey\Uuid\Uuid;

/**
 * @psalm-immutable
 * @template T of object
 */
final class Id
{
    /** @var non-empty-string */
    private string $value;

    /**
     * @param class-string<T> $class
     * @param non-empty-string $value
     */
    private function __construct(string $class, string $value)
    {
        $this->value = $value;
    }

    public function __clone()
    {
        // This is not allowed to make sure users always use the same instance
        // of an id as it is this instance that is used to keep data in memory
        // as long as the user needs it.
        // If a user where to duplicate an instance for a same underlying value
        // it may result in trying to insert the same aggregate twice to the
        // storage.
        throw new \LogicException('Cloning is not allowed');
    }

    /**
     * @template A of object
     *
     * @param class-string<A> $class
     *
     * @return self<A>
     */
    public static function new(string $class): self
    {
        return new self($class, Uuid::uuid4()->toString());
    }

    /**
     * @template A of object
     * @psalm-pure
     *
     * @param class-string<A> $class
     * @param non-empty-string $value
     *
     * @return self<A>
     */
    public static function of(string $class, string $value): self
    {
        if (!Uuid::isValid($value)) {
            throw new \LogicException("Invalid id value '$value'");
        }

        return new self($class, $value);
    }

    /**
     * @template A of object
     * @psalm-pure
     *
     * @param class-string<A> $class
     *
     * @return pure-callable(non-empty-string): self<A>
     */
    public static function for(string $class): callable
    {
        return static fn(string $value) => self::of($class, $value);
    }

    /**
     * @param self<T> $other
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->value;
    }
}
