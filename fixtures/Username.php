<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Innmind\Specification\{
    Comparator,
    Sign,
    Composable,
};
use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
final class Username implements Comparator
{
    use Composable;

    private Sign $sign;
    private Str $value;

    private function __construct(Sign $sign, Str $value)
    {
        $this->sign = $sign;
        $this->value = $value;
    }

    /**
     * @psalm-pure
     */
    public static function of(Sign $sign, Str $value): self
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

    public function value(): Str
    {
        return $this->value;
    }
}
