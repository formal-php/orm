<?php
declare(strict_types = 1);

namespace Formal\ORM\Specification;

use Innmind\Specification\{
    Comparator,
    Sign,
    Composable,
};

/**
 * This specification should only be used in the implementation of an adapter
 * for this ORM
 *
 * @psalm-immutable
 */
final class Property implements Comparator
{
    use Composable;

    /** @var non-empty-string */
    private string $property;
    private Sign $sign;
    private null|string|int|bool $value;

    /**
     * @param non-empty-string $property
     */
    private function __construct(
        string $property,
        Sign $sign,
        null|string|int|bool $value,
    ) {
        $this->property = $property;
        $this->sign = $sign;
        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $property
     */
    public static function of(
        string $property,
        Sign $sign,
        null|string|int|bool $value,
    ): self {
        return new self($property, $sign, $value);
    }

    public function property(): string
    {
        return $this->property;
    }

    public function sign(): Sign
    {
        return $this->sign;
    }

    public function value(): null|string|int|bool
    {
        return $this->value;
    }
}
