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

    /**
     * @param non-empty-string $property
     * @param null|string|int|float|bool|list<string|int|float|bool|null> $value
     */
    private function __construct(
        private string $property,
        private Sign $sign,
        private null|string|int|float|bool|array $value,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     *
     * @param non-empty-string $property
     * @param null|string|int|float|bool|list<string|int|float|bool|null> $value
     */
    public static function of(
        string $property,
        Sign $sign,
        null|string|int|float|bool|array $value,
    ): self {
        return new self($property, $sign, $value);
    }

    #[\Override]
    public function property(): string
    {
        return $this->property;
    }

    #[\Override]
    public function sign(): Sign
    {
        return $this->sign;
    }

    /**
     * @return null|string|int|float|bool|list<string|int|float|bool|null>
     */
    #[\Override]
    public function value(): null|string|int|float|bool|array
    {
        return $this->value;
    }
}
