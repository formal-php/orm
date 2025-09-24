<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

/**
 * @psalm-immutable
 */
final class Id
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $value
     */
    private function __construct(
        private string $name,
        private string $value,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $name
     * @param non-empty-string $value
     */
    public static function of(string $name, string $value): self
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

    /**
     * @return non-empty-string
     */
    public function value(): string
    {
        return $this->value;
    }
}
