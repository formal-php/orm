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
    /** @var null|string|int|bool|list<string|int|bool|null> */
    private null|string|int|bool|array $value;

    /**
     * @param non-empty-string $property
     * @param null|string|int|bool|list<string|int|bool|null> $value
     */
    private function __construct(
        string $property,
        Sign $sign,
        null|string|int|bool|array $value,
    ) {
        $this->property = $property;
        $this->sign = $sign;
        $this->value = $value;
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     * @param null|string|int|bool|list<string|int|bool|null> $value
     */
    public static function of(
        string $property,
        Sign $sign,
        null|string|int|bool|array $value,
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

    /**
     * @return null|string|int|bool|list<string|int|bool|null>
     */
    public function value(): null|string|int|bool|array
    {
        return $this->value;
    }
}
