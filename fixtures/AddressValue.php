<?php
declare(strict_types = 1);

namespace Fixtures\Formal\ORM;

use Innmind\Specification\{
    Comparator,
    Sign,
    Composable,
};

/**
 * @psalm-immutable
 */
final class AddressValue implements Comparator
{
    use Composable;

    private Sign $sign;
    private string $value;

    private function __construct(Sign $sign, string $value)
    {
        $this->sign = $sign;
        $this->value = $value;
    }

    /**
     * @psalm-pure
     */
    public static function of(Sign $sign, string $value): self
    {
        return new self($sign, $value);
    }

    public function property(): string
    {
        return 'value';
    }

    public function sign(): Sign
    {
        return $this->sign;
    }

    public function value(): string
    {
        return $this->value;
    }
}
