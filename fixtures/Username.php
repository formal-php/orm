<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Innmind\Specification\{
    Comparator,
    Sign,
    Composable,
};
use Innmind\Immutable\{
    Str,
    Sequence,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Username implements Comparator
{
    use Composable;

    private Sign $sign;
    /** @var Maybe<Str>|Sequence<Maybe<Str>> */
    private Maybe|Sequence $value;

    /**
     * @param Maybe<Str>|Sequence<Maybe<Str>> $value
     */
    private function __construct(Sign $sign, Maybe|Sequence $value)
    {
        $this->sign = $sign;
        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param Str|Sequence<Str> $value
     */
    public static function of(Sign $sign, Str|Sequence $value): self
    {
        $value = match (true) {
            $value instanceof Str => Maybe::just($value),
            default => $value->map(Maybe::just(...)),
        };

        return new self($sign, $value);
    }

    public function property(): string
    {
        return 'nameStr';
    }

    public function sign(): Sign
    {
        return $this->sign;
    }

    /**
     * @return Maybe<Str>|Sequence<Maybe<Str>>
     */
    public function value(): Maybe|Sequence
    {
        return $this->value;
    }
}
