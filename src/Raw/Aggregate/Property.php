<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

/**
 * @psalm-immutable
 */
final class Property
{
    /**
     * @param non-empty-string $name
     */
    private function __construct(
        private string $name,
        private null|string|int|float|bool $value,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     */
    public static function of(string $name, null|string|int|float|bool $value): self
    {
        return new self($name, $value);
    }

    /**
     * @return non-empty-string
     */
    public function name(): string
    {
        return $this->name;
    }

    public function value(): null|string|int|float|bool
    {
        return $this->value;
    }
}
