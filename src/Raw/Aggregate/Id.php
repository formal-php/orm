<?php
declare(strict_types = 1);

namespace Formal\ORM\Raw\Aggregate;

/**
 * @psalm-immutable
 */
final class Id
{
    /** @var non-empty-string */
    private string $name;
    /** @var non-empty-string */
    private string $value;

    /**
     * @param non-empty-string $name
     * @param non-empty-string $value
     */
    private function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
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
