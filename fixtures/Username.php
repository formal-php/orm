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
};

/**
 * @psalm-immutable
 */
final class Username implements Comparator
{
    use Composable;

    private Sign $sign;
    /** @var Str|Sequence<Str> */
    private Str|Sequence $value;

    /**
     * @param Str|Sequence<Str> $value
     */
    private function __construct(Sign $sign, Str|Sequence $value)
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
     * @return Str|Sequence<Str>
     */
    public function value(): Str|Sequence
    {
        return $this->value;
    }
}
