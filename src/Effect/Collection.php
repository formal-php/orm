<?php
declare(strict_types = 1);

namespace Formal\ORM\Effect;

use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @template T of Property
 */
final class Collection
{
    /**
     * @param T $first
     * @param Sequence<T> $rest
     */
    private function __construct(
        private Property $first,
        private Sequence $rest,
    ) {
    }

    /**
     * @psalm-pure
     * @template A of Property
     *
     * @param A $first
     * @param A $second
     *
     * @return self<A>
     */
    public static function of(Property $first, Property $second): self
    {
        self::allows($first, $second);

        return new self($first, Sequence::of($second));
    }

    /**
     * @param T $effect
     *
     * @return self<T>
     */
    public function and(Property $effect): self
    {
        self::allows($this->first, $effect);

        return new self($this->first, ($this->rest)($effect));
    }

    /**
     * @param callable(T): T $map
     *
     * @return self<T>
     */
    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            $map($this->first),
            $this->rest->map($map),
        );
    }

    /**
     * @return Sequence<T>
     */
    public function effects(): Sequence
    {
        return $this->rest->prepend(Sequence::of($this->first));
    }

    /**
     * @psalm-pure
     */
    private static function allows(Property $first, Property $second): void
    {
        if ($first::class !== $second::class) {
            throw new \LogicException("It's not possible to mix effects");
        }
    }
}
